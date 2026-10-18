<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\SubmissionFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\LapanganUsaha\LapanganUsahaImportService;
use App\Services\Pengeluaran\PengeluaranImportService;
use App\Models\Rekonsiliasi;
use App\Models\Putaran;
use App\Models\RekonsiliasiPeriode;
use App\Models\DataPdrbLapanganUsaha;
use App\Models\DataPdrbPengeluaran;
use App\Jobs\GenerateDerivedLapanganUsahaJob;
use App\Jobs\GenerateDerivedPengeluaranJob;
use App\Services\Integrasi\IntegrasiPdrbService;
use App\Services\Pdrb\PenyimpananDitolakException;
use App\Services\Pdrb\PeriodeTerdampak;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    public function upload(
        Request $request,
        LapanganUsahaImportService $lapanganUsahaImport,
        PengeluaranImportService $pengeluaranImport,
        IntegrasiPdrbService $integrasi,
        PeriodeTerdampak $periodeTerdampakService
    )
    {
        if (!config('features.table_upload_enabled')) {
            return response()->json([
                'message' => 'Unggah tabel dinonaktifkan sementara untuk testing.',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'modul_id' => 'required',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Ambil wilayah berdasarkan user login
        |--------------------------------------------------------------------------
        */

        $user = Auth::user();

        $wilayahId = $user->wilayah_id;


        $rekonsiliasi = Rekonsiliasi::where('status', 'berlangsung')
            ->latest('id')
            ->first();


        if (!$rekonsiliasi) {

            return response()->json([
                'message' => 'Tidak ada rekonsiliasi yang sedang berlangsung',
            ], 404);

        }


        $putaran = Putaran::where('rekonsiliasi_id', $rekonsiliasi->id)
            ->where('status', 'berlangsung')
            ->latest('nomor')
            ->first();


        if (!$putaran) {
            
            return response()->json([
                'message' => 'Tidak ada putaran yang sedang berlangsung',
            ], 404);

        }


        try {

            DB::beginTransaction();


            $file = $request->file('file');


            $path = $file->store('submission_files');


            /*
            |--------------------------------------------------------------------------
            | Nonaktifkan submission lama
            |--------------------------------------------------------------------------
            */

            Submission::where('putaran_id', $putaran->id)
                ->where('wilayah_id', $wilayahId)
                ->where('modul_id', $request->modul_id)
                ->where('is_aktif', 1)
                ->update([
                    'is_aktif' => 0
                ]);


            /*
            |--------------------------------------------------------------------------
            | Buat submission baru
            |--------------------------------------------------------------------------
            */

            $submission = Submission::create([

                'putaran_id' => $putaran->id,

                'user_id' => $user->id,

                'wilayah_id' => $wilayahId,

                'modul_id' => $request->modul_id,

                'sumber' => 'excel',

                'versi' => (
                    Submission::where('putaran_id', $putaran->id)
                        ->where('wilayah_id', $wilayahId)
                        ->where('modul_id', $request->modul_id)
                        ->max('versi') ?? 0
                ) + 1,

                'is_aktif' => 1,

                'status' => 'terupload',

                'submitted_at' => now(),

            ]);



            SubmissionFile::create([

                'submission_id' => $submission->id,

                'nama_file' => $file->getClientOriginalName(),

                'path_file' => $path,

                'ukuran_file' => $file->getSize(),

                'status_import' => 'diproses',

                'uploaded_at' => now(),

            ]);



            $filePath = Storage::disk('local')->path($path);



            /*
            |--------------------------------------------------------------------------
            | IMPORT DATA
            |--------------------------------------------------------------------------
            */


            $hasilImport = [
                'jumlah_perubahan' => 0,
                'periode_berubah' => [],
            ];


            if ($submission->modul_id == 1) {


                $hasilImport = $lapanganUsahaImport->import(
                    $filePath,
                    $submission
                );


            }



            if ($submission->modul_id == 2) {


                $hasilImport = $pengeluaranImport->import(
                    $filePath,
                    $submission
                );


            }



            $submission->update([

                'jumlah_perubahan' => $hasilImport['jumlah_perubahan'],

            ]);



            SubmissionFile::where(
                'submission_id',
                $submission->id
            )
            ->update([

                'status_import' => 'berhasil',

            ]);



            DB::commit();



            /*
            |--------------------------------------------------------------------------
            | GENERATE INTEGRASI
            |--------------------------------------------------------------------------
            */

            $integrasi->generate(

                $putaran->id,

                $submission->wilayah_id

            );



            /*
            |--------------------------------------------------------------------------
            | AMBIL PERIODE TERDAMPAK
            |--------------------------------------------------------------------------
            | Periode yang source-nya berubah, ditambah periode yang derived-nya
            | bergantung pada periode tersebut (QtQ, YtY, CtC).
            | Tanpa perubahan, derived putaran ini mewarisi hasil sebelumnya.
            */

            $periodeTerdampak = $periodeTerdampakService->untuk(

                $hasilImport['periode_berubah']

            );



            /*
            |--------------------------------------------------------------------------
            | GENERATE DERIVED
            |--------------------------------------------------------------------------
            */

            if ($submission->modul_id == 1) {


                foreach ($periodeTerdampak as $periodeId) {


                    GenerateDerivedLapanganUsahaJob::dispatch(

                        $submission->wilayah_id,

                        $periodeId,

                        $putaran->id

                    );


                }


            }



            if ($submission->modul_id == 2) {


                foreach ($periodeTerdampak as $periodeId) {


                    GenerateDerivedPengeluaranJob::dispatch(

                        $submission->wilayah_id,

                        $periodeId,

                        $putaran->id

                    );


                }


            }



            return response()->json([

                'message' => 'Submission berhasil dibuat',

                'submission_id' => $submission->id,

                'jumlah_perubahan' => $hasilImport['jumlah_perubahan'],

            ], 200);



        } catch (PenyimpananDitolakException $e) {


            DB::rollBack();


            return response()->json([

                'message' => $e->getMessage(),

            ], 422);


        } catch (\Throwable $e) {


        DB::rollBack();


        Log::error('UPLOAD SUBMISSION GAGAL', [

            'message' => $e->getMessage(),

            'file' => $e->getFile(),

            'line' => $e->getLine(),

            'trace' => $e->getTraceAsString(),

            'request' => $request->all(),

        ]);



        return response()->json([

            'message' => 'Submission gagal dibuat',

            'error' => $e->getMessage(),

            'file' => $e->getFile(),

            'line' => $e->getLine(),

        ], 500);
        }
    }

    public function downloadFile($id)
    {

        $file = SubmissionFile::with('submission')
            ->findOrFail($id);


        $user = Auth::user();


        if(
            $user->role_id == 2 &&
            $file->submission->wilayah_id != $user->wilayah_id
        ){

            return response()->json([
                'message'=>'Akses ditolak'
            ],403);

        }


        return Storage::download(
            $file->path_file,
            $file->nama_file
        );

    }
}

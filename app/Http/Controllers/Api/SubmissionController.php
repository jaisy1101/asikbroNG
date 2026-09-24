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

class SubmissionController extends Controller
{
    public function upload(
        Request $request,
        LapanganUsahaImportService $lapanganUsahaImport,
        PengeluaranImportService $pengeluaranImport,
        IntegrasiPdrbService $integrasi
    )
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'modul_id' => 'required',
        ]);


        /*
        |--------------------------------------------------------------------------
        | SEMENTARA UNTUK TESTING
        |--------------------------------------------------------------------------
        */
        $wilayahId = 7;


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

                'user_id' => 9,

                'wilayah_id' => $wilayahId,

                'modul_id' => $request->modul_id,

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


            if ($submission->modul_id == 1) {


                $lapanganUsahaImport->import(
                    $filePath,
                    $submission
                );


                $this->updateMasterLapanganUsaha($submission);


            }



            if ($submission->modul_id == 2) {


                $pengeluaranImport->import(
                    $filePath,
                    $submission
                );


                $this->updateMasterPengeluaran($submission);


            }



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
            | AMBIL PERIODE TERDAMPAK REKONSILIASI
            |--------------------------------------------------------------------------
            */

            $periodeTerdampak = RekonsiliasiPeriode::where(

                'rekonsiliasi_id',

                $rekonsiliasi->id

            )->get();



            /*
            |--------------------------------------------------------------------------
            | GENERATE DERIVED
            |--------------------------------------------------------------------------
            */

            if ($submission->modul_id == 1) {


                foreach ($periodeTerdampak as $periode) {


                    GenerateDerivedLapanganUsahaJob::dispatch(

                        $submission->wilayah_id,

                        $periode->periode_id

                    );


                }


            }



            if ($submission->modul_id == 2) {


                foreach ($periodeTerdampak as $periode) {


                    GenerateDerivedPengeluaranJob::dispatch(

                        $submission->wilayah_id,

                        $periode->periode_id

                    );


                }


            }



            return response()->json([

                'message' => 'Submission berhasil dibuat',

                'submission_id' => $submission->id,

            ], 200);



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

    private function updateMasterLapanganUsaha($submission)
    {
        $data = $submission
            ->dataPdrbLapanganUsaha()
            ->get();


        foreach ($data as $item) {


            DataPdrbLapanganUsaha::whereNull('submission_id')
                ->where('wilayah_id', $item->wilayah_id)
                ->where('periode_id', $item->periode_id)
                ->where('jenis_tabel_id', $item->jenis_tabel_id)
                ->where('kategori_lapus_id', $item->kategori_lapus_id)
                ->update([

                    'nilai' => $item->nilai

                ]);

        }
    }

    private function updateMasterPengeluaran($submission)
    {
        $data = $submission
            ->dataPdrbPengeluaran()
            ->get();


        foreach ($data as $item) {

            DataPdrbPengeluaran::whereNull('submission_id')
                ->where('wilayah_id', $item->wilayah_id)
                ->where('periode_id', $item->periode_id)
                ->where('jenis_tabel_id', $item->jenis_tabel_id)
                ->where('kategori_pengeluaran_id', $item->kategori_pengeluaran_id)
                ->update([

                    'nilai' => $item->nilai

                ]);

        }
    }
} 
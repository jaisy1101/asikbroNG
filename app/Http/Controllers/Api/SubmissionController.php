<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubmissionFile;
use App\Models\Wilayah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\LapanganUsaha\LapanganUsahaImportService;
use App\Services\Pengeluaran\PengeluaranImportService;
use App\Services\Pdrb\PenyimpananDitolakException;
use App\Services\Pdrb\SubmissionService;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    public function upload(
        Request $request,
        LapanganUsahaImportService $lapanganUsahaImport,
        PengeluaranImportService $pengeluaranImport,
        SubmissionService $submissions
    )
    {
        if (!config('features.table_upload_enabled')) {
            return response()->json([
                'message' => 'Unggah tabel dinonaktifkan sementara untuk testing.',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'modul_id' => 'required|in:1,2',
        ]);


        $user = Auth::user();


        // Nama berkas harus mengandung kode wilayah user (mencegah salah pilih
        // berkas milik wilayah lain; wilayah data selalu mengikuti user).
        $kodeWilayah = Wilayah::find($user->wilayah_id)?->kodeBerkas();

        if (
            !$kodeWilayah
            || !str_contains($request->file('file')->getClientOriginalName(), $kodeWilayah)
        ) {

            return response()->json([
                'message' => "Nama berkas harus mengandung kode wilayah Anda ({$kodeWilayah}).",
            ], 422);

        }


        $putaran = $submissions->putaranBerlangsung();


        if (!$putaran) {

            return response()->json([
                'message' => 'Tidak ada putaran yang sedang berlangsung',
            ], 404);

        }


        try {

            DB::beginTransaction();


            $file = $request->file('file');


            $path = $file->store('submission_files');


            $submission = $submissions->buat(
                $user,
                $putaran,
                (int) $request->modul_id,
                'excel'
            );



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

            $import = $submission->modul_id == 1
                ? $lapanganUsahaImport
                : $pengeluaranImport;

            $hasilImport = $import->import(
                $filePath,
                $submission
            );


            $submissions->catatHasil($submission, $hasilImport);



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
            | INTEGRASI DAN DERIVED
            |--------------------------------------------------------------------------
            */

            $submissions->prosesLanjutan($submission, $hasilImport);



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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\SubmissionFile;
use Illuminate\Support\Facades\DB;
use App\Services\LapanganUsaha\LapanganUsahaImportService;
use App\Services\Pengeluaran\PengeluaranImportService;
use App\Models\Rekonsiliasi;
use App\Models\Putaran;

class SubmissionController extends Controller
{
    public function upload(
        Request $request,
        LapanganUsahaImportService $lapanganUsahaImport,
        PengeluaranImportService $pengeluaranImport
    )
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'modul_id' => 'required',
        ]);

        /*
        |----------------------------------------------------------------------
        | SEMENTARA UNTUK TESTING
        |----------------------------------------------------------------------
        | Nanti setelah login/auth aktif, wilayah_id diambil dari user login.
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
            ->where('nomor', 0)
            ->first();

        if (!$putaran) {
            return response()->json([
                'message' => 'Putaran 0 belum tersedia',
            ], 404);
        }

        try {

            DB::beginTransaction();

            $file = $request->file('file');

            $path = $file->store('submission_files');

            Submission::where('putaran_id', $putaran->id)
                ->where('wilayah_id', $wilayahId)
                ->where('modul_id', $request->modul_id)
                ->where('is_aktif', 1)
                ->update([
                    'is_aktif' => 0
                ]);

            $submission = Submission::create([
                'putaran_id' => $putaran->id,
                'user_id' => 7, // SEMENTARA UNTUK TESTING
                'wilayah_id' => $wilayahId,
                'modul_id' => $request->modul_id,
                'versi' => 1,
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

            /*
            |--------------------------------------------------------------------------
            | IMPORT DATA
            |--------------------------------------------------------------------------
            |
            | modul_id:
            | 1 = Lapangan Usaha
            | 2 = Pengeluaran
            |
            */

            $filePath = storage_path('app/private/' . $path);

            if ($submission->modul_id == 1) {

                $lapanganUsahaImport->import(
                    $filePath,
                    $submission
                );

            }

            if ($submission->modul_id == 2) {

                $pengeluaranImport->import(
                    $filePath,
                    $submission
                );

            }

            SubmissionFile::where(
                'submission_id',
                $submission->id
            )->update([
                'status_import' => 'berhasil',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Submission berhasil dibuat',
                'submission_id' => $submission->id,
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Submission gagal dibuat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
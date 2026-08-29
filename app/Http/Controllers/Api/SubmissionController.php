<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\SubmissionFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'putaran_id' => 'required',
            'modul_id' => 'required',
        ]);

        $user = $request->user();

        try {
            DB::beginTransaction();

            $file = $request->file('file');

            $path = $file->store('submission_files');

            $submission = Submission::create([
                'putaran_id' => $request->putaran_id,
                'user_id' => $user->id,
                'wilayah_id' => $user->wilayah_id,
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
                'status_import' => 'belum_diproses',
                'uploaded_at' => now(),
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
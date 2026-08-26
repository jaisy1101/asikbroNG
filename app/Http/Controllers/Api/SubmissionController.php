<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\SubmissionFile;
use Illuminate\Support\Facades\Storage;

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

        // simpan file
        $file = $request->file('file');

        $path = $file->store('submission_files');


        // buat submission
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


        // simpan metadata file
        SubmissionFile::create([
            'submission_id' => $submission->id,
            'nama_file' => $file->getClientOriginalName(),
            'path_file' => $path,
            'ukuran_file' => $file->getSize(),
            'status_import' => 'belum_diproses',
            'uploaded_at' => now(),
        ]);


        return response()->json([
            'message' => 'Submission berhasil dibuat',
            'submission_id' => $submission->id
        ]);
    }
}
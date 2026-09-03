<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionDataController extends Controller
{
    public function show($id)
    {

        $submission = Submission::findOrFail($id);


        if ($submission->modul_id == 1) {

            $data = $submission
                ->dataPdrbLapanganUsaha()
                ->with([
                    'periode',
                    'jenisTabel',
                    'kategori'
                ])
                ->get();


        } elseif ($submission->modul_id == 2) {

            $data = $submission
                ->dataPdrbPengeluaran()
                ->with([
                    'periode',
                    'jenisTabel',
                    'kategori'
                ])
                ->get();


        } else {

            return response()->json([
                'message' => 'Modul tidak ditemukan'
            ], 400);

        }


        return response()->json([
            'submission_id' => $submission->id,
            'modul_id' => $submission->modul_id,
            'data' => $data
        ]);

    }
}
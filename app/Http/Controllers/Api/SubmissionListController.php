<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionListController extends Controller
{
    public function index(Request $request)
    {

        $submissions = Submission::with([
            'user.wilayah',
            'modul',
            'putaran',
            'files'
        ])
        ->where('is_aktif', 1)
        ->orderBy('created_at', 'desc')
        ->get();



        return response()->json([

            'data' => $submissions

        ]);

    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionListController extends Controller
{
    public function index(Request $request)
    {

        $user = Auth::user();


        $query = Submission::with([
            'user.wilayah',
            'modul',
            'putaran',
            'files'
        ])
        ->where('is_aktif', 1);



        if($user->role_id == 2){

            $query->where(
                'wilayah_id',
                $user->wilayah_id
            );

        }



        $submissions = $query
            ->orderBy('created_at', 'desc')
            ->get();



        return response()->json([

            'data' => $submissions

        ]);

    }
}
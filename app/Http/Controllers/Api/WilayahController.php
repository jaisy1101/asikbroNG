<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wilayah;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;


class WilayahController extends Controller
{

    public function index()
    {

        $user = Auth::user();


        if($user->role_id == 2){

            $data = Wilayah::where(
                'id',
                $user->wilayah_id
            )
            ->get();

        }
        else{

            $data = Wilayah::all();

        }


        return response()->json([
            'data'=>$data
        ]);

    }

}
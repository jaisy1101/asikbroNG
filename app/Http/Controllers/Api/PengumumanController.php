<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{

    public function index()
    {

        $data = Pengumuman::latest()
            ->take(5)
            ->get();


        return response()->json([

            'data' => $data

        ]);

    }



    public function store(Request $request)
    {

        $request->validate([

            'isi' => 'required'

        ]);


        $data = Pengumuman::create([

            'isi' => $request->isi

        ]);


        return response()->json([

            'message' => 'berhasil',

            'data' => $data

        ]);

    }

}
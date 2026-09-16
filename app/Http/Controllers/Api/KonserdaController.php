<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilKonserda;

class KonserdaController extends Controller
{

    public function index($putaranId)
    {

        $data = HasilKonserda::with([

            'wilayahParent',

        ])
        ->where(
            'putaran_id',
            $putaranId
        )
        ->orderBy('modul_id')
        ->orderBy('jenis_tabel_id')
        ->orderBy('kategori_id')
        ->get();



        return response()->json([

            'putaran_id' => $putaranId,

            'data' => $data

        ]);

    }


}
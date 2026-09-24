<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrasiPdrb;

class IntegrasiPdrbController extends Controller
{

    public function show($rekonsiliasiId, $wilayahId)
    {

        $data = IntegrasiPdrb::with([
            'wilayah',
            'periode',
            'jenisTabel'
        ])
        ->where(
            'rekonsiliasi_id',
            $rekonsiliasiId
        )
        ->where(
            'wilayah_id',
            $wilayahId
        )
        ->orderBy('periode_id')
        ->get();



        return response()->json([

            'rekonsiliasi_id' => $rekonsiliasiId,

            'wilayah_id' => $wilayahId,

            'table' => $data->map(function($item){

                return [

                    'id' => $item->id,

                    'periode' => $item->periode,

                    'jenis_tabel' => $item->jenisTabel,

                    'total_lapus' => $item->total_lapus,

                    'total_pengeluaran' => $item->total_pengeluaran,

                    'selisih' => $item->selisih,

                    'status' => $item->status

                ];

            })

        ]);

    }

}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrasiPdrb;
use Illuminate\Http\Request;

class IntegrasiPdrbController extends Controller
{

    public function index(Request $request)
    {

        $query = IntegrasiPdrb::with([
            'wilayah',
            'periode',
            'jenisTabel'
        ]);


        if ($request->wilayah_id) {

            $query->where(
                'wilayah_id',
                $request->wilayah_id
            );

        }


        if ($request->rekonsiliasi_id) {

            $query->where(
                'rekonsiliasi_id',
                $request->rekonsiliasi_id
            );

        }


        $data = $query
            ->orderBy('periode_id')
            ->get();


        return response()->json([
            'table' => $data
        ]);

    }

}
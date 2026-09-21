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
            'periode',
            'kategori',
            'detail.wilayah',

        ])
        ->where(
            'putaran_id',
            $putaranId
        )
        ->orderBy('modul_id')
        ->orderBy('jenis_tabel_id')
        ->orderByRaw('kategori_id IS NOT NULL')
        ->orderBy('kategori_id')
        ->get()
        ->map(function ($item) {

            if ($item->kategori_id == null) {

                $item->kategori = [
                    'nama' => 'TOTAL',
                    'kode' => null,
                    'level' => 0,
                ];

            }

            return $item;

        });


        return response()->json([

            'putaran_id' => $putaranId,

            'data' => $data

        ]);

    }

    public function showLapanganUsaha($putaranId, $jenis_tabel_id)
    {

        $data = HasilKonserda::with([

            'wilayahParent',
            'periode',
            'kategori',
            'detail.wilayah',

        ])
        ->where('putaran_id', $putaranId)
        ->where('modul_id', 1)
        ->where('jenis_tabel_id', $jenis_tabel_id)
        ->orderByRaw('kategori_id IS NOT NULL')
        ->orderBy('kategori_id')
        ->get()
        ->sortBy(function($item){

            return $item->periode->tahun .
                str_pad(
                        $item->periode->triwulan,
                        2,
                        '0',
                        STR_PAD_LEFT
                );

        })
        ->values()
        ->map(function ($item) {

            if ($item->kategori_id == null) {

                $item->kategori = [
                    'nama' => 'TOTAL',
                    'kode' => null,
                    'level' => 0,
                ];

            }

            return $item;

        });


        return response()->json([
            'putaran_id' => $putaranId,
            'jenis_tabel_id' => $jenis_tabel_id,
            'data' => $data
        ]);

    }

    public function showPengeluaran($putaranId, $jenis_tabel_id)
    {

        $data = HasilKonserda::with([

            'wilayahParent',
            'periode',
            'kategori',
            'detail.wilayah',

        ])
        ->where('putaran_id', $putaranId)
        ->where('modul_id', 2)
        ->where('jenis_tabel_id', $jenis_tabel_id)
        ->orderByRaw('kategori_id IS NOT NULL')
        ->orderBy('kategori_id')
        ->orderBy('periode_id')
        ->get()
        ->map(function ($item) {

            if ($item->kategori_id == null) {

                $item->kategori = [
                    'nama' => 'TOTAL',
                    'kode' => null,
                    'level' => 0,
                ];

            }

            return $item;

        });


        return response()->json([
            'putaran_id' => $putaranId,
            'jenis_tabel_id' => $jenis_tabel_id,
            'data' => $data
        ]);

    }
}
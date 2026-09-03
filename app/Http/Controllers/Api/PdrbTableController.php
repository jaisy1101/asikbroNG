<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;

class PdrbTableController extends Controller
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


            $result = $data
                ->groupBy('kategori.nama')
                ->map(function ($items) {

                    return [
                        'kategori' => $items->first()->kategori->nama,

                        'data' => $items->map(function ($item) {

                            return [
                                'periode' =>
                                    $item->periode->tahun .
                                    ' Q' .
                                    $item->periode->triwulan,

                                'jenis_tabel_id' =>
                                    $item->jenis_tabel_id,

                                'nilai' =>
                                    $item->nilai,
                            ];

                        })->values()
                    ];

                })->values();


        } elseif ($submission->modul_id == 2) {


            $data = $submission
                ->dataPdrbPengeluaran()
                ->with([
                    'periode',
                    'jenisTabel',
                    'kategori'
                ])
                ->get();



            $result = $data
                ->groupBy('kategori.nama')
                ->map(function ($items) {


                    return [
                        'kategori' => $items->first()->kategori->nama,

                        'data' => $items->map(function ($item) {


                            return [
                                'periode' =>
                                    $item->periode->tahun .
                                    ' Q' .
                                    $item->periode->triwulan,

                                'jenis_tabel_id' =>
                                    $item->jenis_tabel_id,

                                'nilai' =>
                                    $item->nilai,
                            ];


                        })->values()
                    ];


                })->values();

        } else {


            return response()->json([
                'message' => 'Modul tidak ditemukan'
            ], 400);


        }



        return response()->json([

            'submission_id' => $submission->id,

            'modul_id' => $submission->modul_id,

            'table' => $result

        ]);

    }
}
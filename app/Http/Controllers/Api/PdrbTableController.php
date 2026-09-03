<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\DataPdrbLapanganUsaha;
use App\Models\DataPdrbPengeluaran;

class PdrbTableController extends Controller
{
    public function showSubmission($id)
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

    public function showSourceLapanganUsaha($wilayah_id, $jenis_tabel_id)
    {
        $data = DataPdrbLapanganUsaha::where('wilayah_id', $wilayah_id)
            ->where('jenis_tabel_id', $jenis_tabel_id)
            ->with([
                'periode',
                'kategori'
            ])
            ->get();


        $result = $data
            ->groupBy('kategori.nama')
            ->map(function ($items) {

                $row = [];

                $row['kategori'] = $items->first()->kategori->nama;


                foreach ($items as $item) {

                    $periode =
                        $item->periode->tahun .
                        ' Q' .
                        $item->periode->triwulan;


                    $row[$periode] = $item->nilai;

                }


                return $row;

            })
            ->values();



        return response()->json([

            'wilayah_id' => $wilayah_id,

            'jenis_tabel_id' => $jenis_tabel_id,

            'table' => $result

        ]);
    }

    public function showSourcePengeluaran($wilayah_id, $jenis_tabel_id)
    {
        $data = DataPdrbPengeluaran::where('wilayah_id', $wilayah_id)
            ->where('jenis_tabel_id', $jenis_tabel_id)
            ->with([
                'periode',
                'kategori'
            ])
            ->get();



        $result = $data
            ->groupBy('kategori.nama')
            ->map(function ($items) {

                $row = [];

                $row['kategori'] = $items->first()->kategori->nama;


                foreach ($items as $item) {

                    $periode =
                        $item->periode->tahun .
                        ' Q' .
                        $item->periode->triwulan;


                    $row[$periode] = $item->nilai;

                }


                return $row;

            })
            ->values();



        return response()->json([

            'wilayah_id' => $wilayah_id,

            'jenis_tabel_id' => $jenis_tabel_id,

            'table' => $result

        ]);
    }
    
}
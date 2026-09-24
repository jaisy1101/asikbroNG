<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Rekonsiliasi;
use App\Models\Periode;
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

    public function showLapanganUsaha($wilayah_id, $jenis_tabel_id)
    {
        $rekonsiliasi = Rekonsiliasi::where('status', 'berlangsung')
            ->latest('id')
            ->first();


        $periodeAktif = null;

        if ($rekonsiliasi) {
            $periodeAktif = $rekonsiliasi->periode;
        }


        $data = DataPdrbLapanganUsaha::where('wilayah_id', $wilayah_id)
            ->where('jenis_tabel_id', $jenis_tabel_id)
            ->when($periodeAktif, function ($query) use ($periodeAktif) {

                $query->whereHas('periode', function ($q) use ($periodeAktif) {

                    $q->where('tahun', '<', $periodeAktif->tahun)
                    ->orWhere(function ($q2) use ($periodeAktif) {

                        $q2->where('tahun', $periodeAktif->tahun)
                        ->where('triwulan', '<=', $periodeAktif->triwulan);

                    });

                });

            })
            ->with([
                'periode',
                'kategori.parent'
            ])
            ->get();



        $result = $data
            ->groupBy('kategori.id')
            ->map(function ($items) {


                $row = [];

                $kategori = $items->first()->kategori;

                $row['kategori'] = $kategori->nama;

                $row['kode'] = $kategori->kode;

                $row['level'] = $kategori->level;


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

    public function showPengeluaran($wilayah_id, $jenis_tabel_id)
    {
        $rekonsiliasi = Rekonsiliasi::where('status', 'berlangsung')
            ->latest('id')
            ->first();


        $periodeAktif = null;

        if ($rekonsiliasi) {
            $periodeAktif = $rekonsiliasi->periode;
        }



        $data = DataPdrbPengeluaran::where('wilayah_id', $wilayah_id)
            ->where('jenis_tabel_id', $jenis_tabel_id)
            ->when($periodeAktif, function ($query) use ($periodeAktif) {

                $query->whereHas('periode', function ($q) use ($periodeAktif) {

                    $q->where('tahun', '<', $periodeAktif->tahun)
                    ->orWhere(function ($q2) use ($periodeAktif) {

                        $q2->where('tahun', $periodeAktif->tahun)
                        ->where('triwulan', '<=', $periodeAktif->triwulan);

                    });

                });

            })
            ->with([
                'periode',
                'kategori.parent'
            ])
            ->get();



        $result = $data
            ->groupBy('kategori.id')
            ->map(function ($items) {


                $row = [];

                $kategori = $items->first()->kategori;


                $row['kategori'] = $kategori->nama;

                $row['kode'] = $kategori->kode;

                $row['level'] = $kategori->level;



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
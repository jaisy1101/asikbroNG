<?php

namespace App\Services\Integrasi;

use App\Models\IntegrasiPdrb;
use App\Models\DataPdrbLapanganUsaha;
use App\Models\DataPdrbPengeluaran;
use App\Models\Wilayah;
use App\Models\RekonsiliasiPeriode;
use App\Models\Putaran;

class IntegrasiPdrbService
{

    public function generate($putaranId, $wilayahId)
    {


        $putaran = Putaran::findOrFail(
            $putaranId
        );


        $periodeList = RekonsiliasiPeriode::where(
            'rekonsiliasi_id',
            $putaran->rekonsiliasi_id
        )
        ->get();



        $wilayahList = Wilayah::where(
            'id',
            $wilayahId
        )->get();



        foreach ($wilayahList as $wilayah) {


            foreach ($periodeList as $periode) {


                foreach ([1,2] as $jenisTabelId) {



                    $totalLapangan = DataPdrbLapanganUsaha::whereNull('data_pdrb_lapangan_usaha.submission_id')
                        ->where('data_pdrb_lapangan_usaha.wilayah_id', $wilayah->id)
                        ->where('data_pdrb_lapangan_usaha.periode_id', $periode->periode_id)
                        ->where('data_pdrb_lapangan_usaha.jenis_tabel_id', $jenisTabelId)
                        ->whereHas('kategori', function ($query) {

                            $query->whereNull('parent_id');

                        })
                        ->sum('nilai');



                    $totalPengeluaran = DataPdrbPengeluaran::whereNull('data_pdrb_pengeluaran.submission_id')
                        ->where('data_pdrb_pengeluaran.wilayah_id', $wilayah->id)
                        ->where('data_pdrb_pengeluaran.periode_id', $periode->periode_id)
                        ->where('data_pdrb_pengeluaran.jenis_tabel_id', $jenisTabelId)
                        ->whereHas('kategori', function ($query) {

                            $query->whereNull('parent_id');

                        })
                        ->sum('nilai');



                    $selisih = $totalLapangan - $totalPengeluaran;



                    IntegrasiPdrb::updateOrCreate(

                        [

                            'putaran_id' => $putaranId,

                            'wilayah_id' => $wilayah->id,

                            'periode_id' => $periode->periode_id,

                            'jenis_tabel_id' => $jenisTabelId,

                        ],

                        [

                            'total_lapus' => $totalLapangan,

                            'total_pengeluaran' => $totalPengeluaran,

                            'selisih' => $selisih,

                            'status' => abs($selisih) <= 0.05
                                ? 'sesuai'
                                : 'selisih',

                        ]

                    );


                }

            }


        }


    }

}
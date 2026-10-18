<?php

namespace App\Services\Integrasi;

use App\Models\IntegrasiPdrb;
use App\Models\KategoriLapanganUsaha;
use App\Models\KategoriPengeluaran;
use App\Models\Wilayah;
use App\Models\RekonsiliasiPeriode;
use App\Models\Putaran;
use App\Services\Pdrb\EffectiveValueService;
use App\Services\Pdrb\PdrbModul;

class IntegrasiPdrbService
{

    /*
    |--------------------------------------------------------------------------
    | INTEGRASI LAPANGAN USAHA - PENGELUARAN
    |--------------------------------------------------------------------------
    |
    | Total = jumlah kategori induk (parent_id NULL) dari nilai efektif
    | pada putaran yang diproses: data dasar + revisi semua putaran
    | dengan putaran.id <= putaran ini.
    |
    */

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


        if ($wilayahList->isEmpty() || $periodeList->isEmpty()) {
            return;
        }


        $effective = app(EffectiveValueService::class);

        $periodeIds = $periodeList->pluck('periode_id')->all();

        $induk = [
            PdrbModul::LAPANGAN_USAHA => KategoriLapanganUsaha::whereNull('parent_id')
                ->pluck('id')
                ->all(),
            PdrbModul::PENGELUARAN => KategoriPengeluaran::whereNull('parent_id')
                ->pluck('id')
                ->all(),
        ];



        foreach ($wilayahList as $wilayah) {


            $nilaiLapangan = $effective->source(
                PdrbModul::LAPANGAN_USAHA,
                $wilayah->id,
                $periodeIds,
                [1, 2],
                (int) $putaranId
            );

            $nilaiPengeluaran = $effective->source(
                PdrbModul::PENGELUARAN,
                $wilayah->id,
                $periodeIds,
                [1, 2],
                (int) $putaranId
            );


            foreach ($periodeList as $periode) {


                foreach ([1,2] as $jenisTabelId) {



                    $totalLapangan = $this->totalInduk(
                        $nilaiLapangan,
                        $wilayah->id,
                        $periode->periode_id,
                        $jenisTabelId,
                        $induk[PdrbModul::LAPANGAN_USAHA]
                    );



                    $totalPengeluaran = $this->totalInduk(
                        $nilaiPengeluaran,
                        $wilayah->id,
                        $periode->periode_id,
                        $jenisTabelId,
                        $induk[PdrbModul::PENGELUARAN]
                    );



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



    private function totalInduk(
        $nilai,
        $wilayahId,
        $periodeId,
        $jenisTabelId,
        array $kategoriIndukIds
    ): float {

        $total = 0;

        foreach ($kategoriIndukIds as $kategoriId) {

            $row = $nilai->get(EffectiveValueService::kunci(
                $wilayahId,
                $periodeId,
                $jenisTabelId,
                $kategoriId
            ));

            if ($row) {
                $total += (float) $row->nilai;
            }
        }

        return $total;
    }

}

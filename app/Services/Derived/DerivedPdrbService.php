<?php

namespace App\Services\Derived;

use App\Models\DataPdrbLapanganUsaha;
use Illuminate\Support\Facades\DB;

class DerivedPdrbService
{

    public function hitungDistribusi($wilayahId, $periodeId)
    {

        DB::transaction(function () use ($wilayahId, $periodeId) {


            // 1. Ambil ADHB source
            $source = DataPdrbLapanganUsaha::where([
                'wilayah_id' => $wilayahId,
                'periode_id' => $periodeId,
                'jenis_tabel_id' => 1, // ADHB
                'tipe_data' => 'source'
            ])
            ->get();


            if ($source->isEmpty()) {
                return;
            }


            // 2. Ambil kategori parent untuk total PDRB
            $totalPdrb = $source
                ->filter(function ($item) {

                    return $item->kategori 
                        && $item->kategori->parent_id === null;

                })
                ->sum('nilai');



            if ($totalPdrb == 0) {
                return;
            }



            // 3. Hapus derived distribusi lama
            DataPdrbLapanganUsaha::where([
                'wilayah_id' => $wilayahId,
                'periode_id' => $periodeId,
                'jenis_tabel_id' => 3
            ])
            ->delete();



            // 4. Buat hasil distribusi
            foreach ($source as $item) {


                $nilaiDistribusi =
                    ($item->nilai / $totalPdrb) * 100;



                DataPdrbLapanganUsaha::create([

                    'submission_id' => null,

                    'wilayah_id' => $wilayahId,

                    'periode_id' => $periodeId,

                    'jenis_tabel_id' => 3,

                    'kategori_lapus_id' => $item->kategori_lapus_id,

                    'nilai' => round($nilaiDistribusi,2),

                    'tipe_data' => 'derived'

                ]);

            }


        });

    }

}
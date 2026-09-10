<?php

namespace App\Services\Derived;

use App\Models\DataPdrbLapanganUsaha;
use Illuminate\Support\Facades\DB;

class DerivedLapanganUsaha
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

    private function getPeriodeQtQ($periodeId)
    {
        $periode = \App\Models\Periode::find($periodeId);

        if (!$periode) {
            return null;
        }


        if ($periode->triwulan == 1) {

            return \App\Models\Periode::where('tahun', $periode->tahun - 1)
                ->where('triwulan', 4)
                ->first();

        }


        return \App\Models\Periode::where('tahun', $periode->tahun)
            ->where('triwulan', $periode->triwulan - 1)
            ->first();

    }

    public function hitungQtQ($wilayahId, $periodeId)
    {

        $periodeSebelumnya = $this->getPeriodeQtQ($periodeId);


        if (!$periodeSebelumnya) {
            return;
        }


        $sourceSekarang = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 2,
            'tipe_data' => 'source'
        ])->get();


        $sourceSebelumnya = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeSebelumnya->id,
            'jenis_tabel_id' => 2,
            'tipe_data' => 'source'
        ])
        ->get()
        ->keyBy('kategori_lapus_id');



        foreach ($sourceSekarang as $item) {


            $sebelumnya = $sourceSebelumnya[$item->kategori_lapus_id] ?? null;


            if (!$sebelumnya || $sebelumnya->nilai == 0) {
                continue;
            }


            $qtq =
                (($item->nilai / $sebelumnya->nilai) * 100) - 100;



            DataPdrbLapanganUsaha::create([

                'submission_id' => null,

                'wilayah_id' => $wilayahId,

                'periode_id' => $periodeId,

                'jenis_tabel_id' => 4,

                'kategori_lapus_id' => $item->kategori_lapus_id,

                'nilai' => round($qtq,2),

                'tipe_data' => 'derived'

            ]);

        }

    }

    private function getPeriodeYtY($periodeId)
    {
        $periode = \App\Models\Periode::find($periodeId);

        if (!$periode) {
            return null;
        }


        return \App\Models\Periode::where('tahun', $periode->tahun - 1)
            ->where('triwulan', $periode->triwulan)
            ->first();
    }

    public function hitungYtY($wilayahId, $periodeId)
    {

        $periodeSebelumnya = $this->getPeriodeYtY($periodeId);


        if (!$periodeSebelumnya) {
            return;
        }


        $sourceSekarang = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 2,
            'tipe_data' => 'source'
        ])
        ->get();



        $sourceTahunLalu = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeSebelumnya->id,
            'jenis_tabel_id' => 2,
            'tipe_data' => 'source'
        ])
        ->get()
        ->keyBy('kategori_lapus_id');



        // hapus hasil lama
        DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 5
        ])
        ->delete();



        foreach ($sourceSekarang as $item) {


            $tahunLalu = $sourceTahunLalu[$item->kategori_lapus_id] ?? null;


            if (!$tahunLalu || $tahunLalu->nilai == 0) {
                continue;
            }



            $yty =
                (($item->nilai / $tahunLalu->nilai) * 100) - 100;



            DataPdrbLapanganUsaha::create([

                'submission_id' => null,

                'wilayah_id' => $wilayahId,

                'periode_id' => $periodeId,

                'jenis_tabel_id' => 5,

                'kategori_lapus_id' => $item->kategori_lapus_id,

                'nilai' => round($yty,2),

                'tipe_data' => 'derived'

            ]);

        }

    }

    private function getPeriodeCTC($tahun, $triwulan)
    {
        return \App\Models\Periode::where('tahun', $tahun)
            ->where('triwulan', '<=', $triwulan)
            ->get();
    }

    public function hitungCtC($wilayahId, $periodeId)
    {

        $periode = \App\Models\Periode::find($periodeId);


        if (!$periode) {
            return;
        }


        $periodeSekarang = $this->getPeriodeCTC(
            $periode->tahun,
            $periode->triwulan
        );


        $periodeTahunLalu = $this->getPeriodeCTC(
            $periode->tahun - 1,
            $periode->triwulan
        );


        $sourceSekarang = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'jenis_tabel_id' => 2,
            'tipe_data' => 'source'
        ])
        ->whereIn(
            'periode_id',
            $periodeSekarang->pluck('id')
        )
        ->get()
        ->groupBy('kategori_lapus_id');



        $sourceTahunLalu = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'jenis_tabel_id' => 2,
            'tipe_data' => 'source'
        ])
        ->whereIn(
            'periode_id',
            $periodeTahunLalu->pluck('id')
        )
        ->get()
        ->groupBy('kategori_lapus_id');



        DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 6
        ])
        ->delete();



        foreach ($sourceSekarang as $kategoriId => $dataSekarang) {


            $dataLalu = $sourceTahunLalu[$kategoriId] ?? null;


            if (!$dataLalu) {
                continue;
            }


            $totalSekarang = $dataSekarang->sum('nilai');

            $totalLalu = $dataLalu->sum('nilai');


            if ($totalLalu == 0) {
                continue;
            }


            $ctc =
                (($totalSekarang / $totalLalu) * 100) - 100;



            DataPdrbLapanganUsaha::create([

                'submission_id' => null,

                'wilayah_id' => $wilayahId,

                'periode_id' => $periodeId,

                'jenis_tabel_id' => 6,

                'kategori_lapus_id' => $kategoriId,

                'nilai' => round($ctc,2),

                'tipe_data' => 'derived'

            ]);

        }

    }

    public function hitungImplisit($wilayahId, $periodeId)
    {

        $adhb = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 1,
            'tipe_data' => 'source'
        ])
        ->get()
        ->keyBy('kategori_lapus_id');



        $adhk = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 2,
            'tipe_data' => 'source'
        ])
        ->get()
        ->keyBy('kategori_lapus_id');



        if ($adhb->isEmpty() || $adhk->isEmpty()) {
            return;
        }



        // hapus hasil lama
        DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 7
        ])
        ->delete();



        foreach ($adhb as $kategoriId => $dataAdhb) {


            $dataAdhk = $adhk[$kategoriId] ?? null;


            if (!$dataAdhk) {
                continue;
            }


            if ($dataAdhk->nilai == 0) {

                $implisit = 0;

            } else {

                $implisit =
                    ($dataAdhb->nilai / $dataAdhk->nilai) * 100;

            }



            DataPdrbLapanganUsaha::create([

                'submission_id' => null,

                'wilayah_id' => $wilayahId,

                'periode_id' => $periodeId,

                'jenis_tabel_id' => 7,

                'kategori_lapus_id' => $kategoriId,

                'nilai' => round($implisit,2),

                'tipe_data' => 'derived'

            ]);

        }

    }

    public function hitungImplisitQtQ($wilayahId, $periodeId)
    {

        $periodeSebelumnya = $this->getPeriodeQtQ($periodeId);


        if (!$periodeSebelumnya) {
            return;
        }


        $sourceSekarang = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 7,
            'tipe_data' => 'derived'
        ])
        ->get()
        ->keyBy('kategori_lapus_id');



        $sourceSebelumnya = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeSebelumnya->id,
            'jenis_tabel_id' => 7,
            'tipe_data' => 'derived'
        ])
        ->get()
        ->keyBy('kategori_lapus_id');



        DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 8
        ])
        ->delete();



        foreach ($sourceSekarang as $kategoriId => $item) {


            $sebelumnya = $sourceSebelumnya[$kategoriId] ?? null;


            if (!$sebelumnya || $sebelumnya->nilai == 0) {
                continue;
            }



            $qtq =
                (($item->nilai / $sebelumnya->nilai) * 100) - 100;



            DataPdrbLapanganUsaha::create([

                'submission_id' => null,

                'wilayah_id' => $wilayahId,

                'periode_id' => $periodeId,

                'jenis_tabel_id' => 8,

                'kategori_lapus_id' => $kategoriId,

                'nilai' => round($qtq,2),

                'tipe_data' => 'derived'

            ]);

        }

    }

    public function hitungImplisitYtY($wilayahId, $periodeId)
    {

        $periodeSebelumnya = $this->getPeriodeYtY($periodeId);


        if (!$periodeSebelumnya) {
            return;
        }


        $sourceSekarang = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 7,
            'tipe_data' => 'derived'
        ])
        ->get()
        ->keyBy('kategori_lapus_id');



        $sourceTahunLalu = DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeSebelumnya->id,
            'jenis_tabel_id' => 7,
            'tipe_data' => 'derived'
        ])
        ->get()
        ->keyBy('kategori_lapus_id');



        DataPdrbLapanganUsaha::where([
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => 9
        ])
        ->delete();



        foreach ($sourceSekarang as $kategoriId => $item) {


            $tahunLalu = $sourceTahunLalu[$kategoriId] ?? null;


            if (!$tahunLalu || $tahunLalu->nilai == 0) {
                continue;
            }



            $yty =
                (($item->nilai / $tahunLalu->nilai) * 100) - 100;



            DataPdrbLapanganUsaha::create([

                'submission_id' => null,

                'wilayah_id' => $wilayahId,

                'periode_id' => $periodeId,

                'jenis_tabel_id' => 9,

                'kategori_lapus_id' => $kategoriId,

                'nilai' => round($yty,2),

                'tipe_data' => 'derived'

            ]);

        }

    }
}
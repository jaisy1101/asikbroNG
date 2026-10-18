<?php

namespace App\Services\Konserda;

use App\Models\Putaran;
use App\Models\RekonsiliasiPeriode;
use App\Models\Wilayah;
use App\Models\KategoriLapanganUsaha;
use App\Models\KategoriPengeluaran;
use App\Models\HasilKonserda;
use App\Models\HasilKonserdaDetail;
use App\Services\Pdrb\EffectiveValueService;
use App\Services\Pdrb\PdrbModul;
use Illuminate\Support\Facades\Log;

class KonserdaService
{

    /*
    |--------------------------------------------------------------------------
    | GENERATE KONSERDA
    |--------------------------------------------------------------------------
    |
    | Nilai provinsi dan kabkota memakai nilai efektif pada putaran yang
    | diproses: data dasar + revisi semua putaran dengan putaran.id <= putaran
    | ini. Setiap sel hanya dihitung satu kali (tidak menjumlahkan versi).
    |
    */

    public function generate($putaranId)
    {
        HasilKonserdaDetail::whereHas('hasilKonserda', function($q) use ($putaranId){

            $q->where('putaran_id', $putaranId);

        })->delete();

        HasilKonserda::where(
            'putaran_id',
            $putaranId
        )->delete();

        Log::info('SELESAI DELETE KONSERDA');

        $putaran = Putaran::findOrFail($putaranId);


        $periodeList = RekonsiliasiPeriode::where(
            'rekonsiliasi_id',
            $putaran->rekonsiliasi_id
        )
        ->with('periode')
        ->orderBy('periode_id')
        ->get();



        // ambil provinsi
        $provinsi = Wilayah::where(
            'jenis',
            'provinsi'
        )->first();

        if (!$provinsi) {

            throw new \Exception(
                'Provinsi tidak ditemukan'
            );

        }

        // ambil kab/kota
        $kabkota = Wilayah::where(
            'parent_id',
            $provinsi->id
        )->get();

        $wilayahIds = $kabkota->pluck('id')
            ->push($provinsi->id)
            ->all();

        $modulList = [

            [
                'modul_id' => PdrbModul::LAPANGAN_USAHA,
                'kategori_parent' => KategoriLapanganUsaha::whereNull('parent_id')->get(),
                'kategori_list' => KategoriLapanganUsaha::all(),
            ],

            [
                'modul_id' => PdrbModul::PENGELUARAN,
                'kategori_parent' => KategoriPengeluaran::whereNull('parent_id')->get(),
                'kategori_list' => KategoriPengeluaran::all(),
            ],

        ];


        $jenisTabelList = [1,2];

        $effective = app(EffectiveValueService::class);



        foreach ($periodeList as $periode) {


            foreach ($modulList as $modul) {


                foreach ($jenisTabelList as $jenisTabelId) {


                    $nilaiEfektif = $effective->source(
                        $modul['modul_id'],
                        $wilayahIds,
                        [$periode->periode_id],
                        [$jenisTabelId],
                        (int) $putaran->id
                    );

                    $nilai = function ($wilayahId, $kategoriIds) use (
                        $nilaiEfektif,
                        $periode,
                        $jenisTabelId
                    ) {

                        $total = 0;

                        foreach ($kategoriIds as $kategoriId) {

                            $row = $nilaiEfektif->get(EffectiveValueService::kunci(
                                $wilayahId,
                                $periode->periode_id,
                                $jenisTabelId,
                                $kategoriId
                            ));

                            if ($row) {
                                $total += (float) $row->nilai;
                            }
                        }

                        return $total;
                    };



                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL
                    |--------------------------------------------------------------------------
                    */

                    $parentIds = $modul['kategori_parent']->pluck('id')->all();

                    $this->simpanHasil(
                        $putaran->id,
                        $periode->periode_id,
                        $provinsi->id,
                        $modul['modul_id'],
                        $jenisTabelId,
                        null,
                        $nilai($provinsi->id, $parentIds),
                        $kabkota->mapWithKeys(fn ($wilayah) => [
                            $wilayah->id => $nilai($wilayah->id, $parentIds),
                        ])->all(),
                        2,
                        fn ($diskrepansi) => $this->cekStatusTotal($diskrepansi)
                    );



                    /*
                    |--------------------------------------------------------------------------
                    | SEMUA KATEGORI
                    |--------------------------------------------------------------------------
                    */


                    foreach ($modul['kategori_list'] as $kategori) {

                        $this->simpanHasil(
                            $putaran->id,
                            $periode->periode_id,
                            $provinsi->id,
                            $modul['modul_id'],
                            $jenisTabelId,
                            $kategori->id,
                            $nilai($provinsi->id, [$kategori->id]),
                            $kabkota->mapWithKeys(fn ($wilayah) => [
                                $wilayah->id => $nilai($wilayah->id, [$kategori->id]),
                            ])->all(),
                            5,
                            fn ($diskrepansi) => $this->cekStatusKategori($diskrepansi)
                        );

                    }

                }
            }
        }
    }



    /*
    | $nilaiKabkota: [wilayah_id => nilai]
    */
    private function simpanHasil(
        $putaranId,
        $periodeId,
        $provinsiId,
        $modulId,
        $jenisTabelId,
        $kategoriId,
        float $nilaiProvinsi,
        array $nilaiKabkota,
        $batasToleransi,
        callable $cekStatus
    ): void {

        $nilaiAgregasi = array_sum($nilaiKabkota);

        $selisih = $nilaiProvinsi - $nilaiAgregasi;


        $diskrepansi = 0;


        if ($nilaiProvinsi != 0) {

            $diskrepansi =
                abs($selisih)
                /
                abs($nilaiProvinsi)
                *
                100;

        }


        $hasil = HasilKonserda::create([

            'putaran_id' => $putaranId,

            'periode_id' => $periodeId,

            'wilayah_parent_id' => $provinsiId,

            'modul_id' => $modulId,

            'jenis_tabel_id' => $jenisTabelId,

            'kategori_id' => $kategoriId,

            'nilai_provinsi' => $nilaiProvinsi,

            'nilai_agregasi_kabkota' => $nilaiAgregasi,

            'selisih' => $selisih,

            'diskrepansi_persen' => $diskrepansi,

            'batas_toleransi' => $batasToleransi,

            'status' => $cekStatus($diskrepansi),

        ]);


        if (empty($nilaiKabkota)) {
            return;
        }


        $now = now();

        $detail = [];

        foreach ($nilaiKabkota as $wilayahId => $nilai) {

            $detail[] = [

                'hasil_konserda_id' => $hasil->id,

                'wilayah_id' => $wilayahId,

                'nilai' => $nilai,

                'created_at' => $now,

                'updated_at' => $now,

            ];

        }

        HasilKonserdaDetail::insert($detail);
    }



    private function cekStatusTotal(float $nilai)
    {

        if ($nilai < 2) {

            return 'aman';

        }


        if ($nilai < 10) {

            return 'peringatan';

        }


        return 'ekstrem';

    }



    private function cekStatusKategori(float $nilai)
    {

        if ($nilai < 5) {

            return 'aman';

        }


        if ($nilai < 10) {

            return 'peringatan';

        }


        return 'ekstrem';

    }

}

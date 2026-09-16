<?php

namespace App\Services\Konserda;

use App\Models\Putaran;
use App\Models\RekonsiliasiPeriode;
use App\Models\DataPdrbLapanganUsaha;
use App\Models\DataPdrbPengeluaran;
use App\Models\Wilayah;
use App\Models\KategoriLapanganUsaha;
use App\Models\KategoriPengeluaran;
use App\Models\HasilKonserda;
use App\Models\HasilKonserdaDetail;
use Illuminate\Support\Facades\Log;

class KonserdaService
{

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
        )->get();



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

        $modulList = [

            [
                'modul_id' => 1,
                'model' => DataPdrbLapanganUsaha::class,
                'kategori_column' => 'kategori_lapus_id',
            ],

            [
                'modul_id' => 2,
                'model' => DataPdrbPengeluaran::class,
                'kategori_column' => 'kategori_pengeluaran_id',
            ],

        ];


        $jenisTabelList = [1,2];



        foreach ($periodeList as $periode) {


            foreach ($modulList as $modul) {


                foreach ($jenisTabelList as $jenisTabelId) {
                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL PDRB
                    |--------------------------------------------------------------------------
                    */


                    if ($modul['modul_id'] == 1) {

                        $kategoriParent = KategoriLapanganUsaha::whereNull('parent_id')->get();

                        $kategoriList = KategoriLapanganUsaha::all();

                    } else {

                        $kategoriParent = KategoriPengeluaran::whereNull('parent_id')->get();

                        $kategoriList = KategoriPengeluaran::all();

                    }


                    $model = $modul['model'];

                    $kategoriColumn = $modul['kategori_column'];



                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL
                    |--------------------------------------------------------------------------
                    */


                    $nilaiProvinsiTotal = $model::where([
                        'wilayah_id' => $provinsi->id,
                        'periode_id' => $periode->periode_id,
                        'jenis_tabel_id' => $jenisTabelId,
                    ])
                    ->whereIn(
                        $kategoriColumn,
                        $kategoriParent->pluck('id')
                    )
                    ->sum('nilai');



                    $nilaiKabkotaTotal = $model::whereIn(
                        'wilayah_id',
                        $kabkota->pluck('id')
                    )
                    ->where([
                        'periode_id' => $periode->periode_id,
                        'jenis_tabel_id' => $jenisTabelId,
                    ])
                    ->whereIn(
                        $kategoriColumn,
                        $kategoriParent->pluck('id')
                    )
                    ->sum('nilai');



                    $selisihTotal = $nilaiProvinsiTotal - $nilaiKabkotaTotal;


                    $diskrepansiTotal = 0;


                    if ($nilaiProvinsiTotal != 0) {

                        $diskrepansiTotal =
                            abs($selisihTotal)
                            /
                            abs($nilaiProvinsiTotal)
                            *
                            100;

                    }



                    HasilKonserda::create([

                        'putaran_id' => $putaran->id,

                        'periode_id' => $periode->periode_id,

                        'wilayah_parent_id' => $provinsi->id,

                        'modul_id' => $modul['modul_id'],

                        'jenis_tabel_id' => $jenisTabelId,

                        'kategori_id' => null,

                        'nilai_provinsi' => $nilaiProvinsiTotal,

                        'nilai_agregasi_kabkota' => $nilaiKabkotaTotal,

                        'selisih' => $selisihTotal,

                        'diskrepansi_persen' => $diskrepansiTotal,

                        'batas_toleransi' => 2,

                        'status' => $this->cekStatus(
                            $diskrepansiTotal,
                            2
                        ),

                    ]);



                    /*
                    |--------------------------------------------------------------------------
                    | SEMUA KATEGORI
                    |--------------------------------------------------------------------------
                    */


                    foreach ($kategoriList as $kategori) {


                        $nilaiProvinsi = $model::where([
                            'wilayah_id' => $provinsi->id,
                            'periode_id' => $periode->periode_id,
                            'jenis_tabel_id' => $jenisTabelId,
                            $kategoriColumn => $kategori->id,
                        ])
                        ->sum('nilai');



                        $nilaiKabkota = $model::whereIn(
                            'wilayah_id',
                            $kabkota->pluck('id')
                        )
                        ->where([
                            'periode_id' => $periode->periode_id,
                            'jenis_tabel_id' => $jenisTabelId,
                            $kategoriColumn => $kategori->id,
                        ])
                        ->sum('nilai');



                        $selisih = $nilaiProvinsi - $nilaiKabkota;



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

                            'putaran_id' => $putaran->id,

                            'periode_id' => $periode->periode_id,

                            'wilayah_parent_id' => $provinsi->id,

                            'modul_id' => $modul['modul_id'],

                            'jenis_tabel_id' => $jenisTabelId,

                            'kategori_id' => $kategori->id,

                            'nilai_provinsi' => $nilaiProvinsi,

                            'nilai_agregasi_kabkota' => $nilaiKabkota,

                            'selisih' => $selisih,

                            'diskrepansi_persen' => $diskrepansi,

                            'batas_toleransi' => 5,

                            'status' => $this->cekStatus(
                                $diskrepansi,
                                5
                            ),

                        ]);



                        foreach ($kabkota as $wilayah) {


                            $nilai = $model::where([
                                'wilayah_id' => $wilayah->id,
                                'periode_id' => $periode->periode_id,
                                'jenis_tabel_id' => $jenisTabelId,
                                $kategoriColumn => $kategori->id,
                            ])
                            ->sum('nilai');



                            HasilKonserdaDetail::create([

                                'hasil_konserda_id' => $hasil->id,

                                'wilayah_id' => $wilayah->id,

                                'nilai' => $nilai,

                            ]);

                        }


                    } 

                }
            }
        }
    }

                        

    private function cekStatus(float $nilai, float $batas)
    {

        if ($nilai <= $batas) {

            return 'aman';

        }


        if ($nilai <= ($batas * 2.5)) {

            return 'peringatan';

        }


        return 'ekstrem';

    }

}
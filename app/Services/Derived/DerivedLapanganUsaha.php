<?php

namespace App\Services\Derived;

use App\Models\DataPdrbLapanganUsaha;
use App\Models\Periode;
use App\Models\Putaran;
use App\Services\Pdrb\EffectiveValueService;
use App\Services\Pdrb\PdrbModul;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DerivedLapanganUsaha
{
    /*
    |--------------------------------------------------------------------------
    | IDENTITAS KATEGORI
    |--------------------------------------------------------------------------
    */

    private function kategoriKey($kategoriId): string
    {
        return $kategoriId === null
            ? '__TOTAL__'
            : (string) $kategoriId;
    }


    /*
    |--------------------------------------------------------------------------
    | AMBIL SOURCE EFEKTIF BERDASARKAN PUTARAN
    |--------------------------------------------------------------------------
    |
    | Mengikuti EffectiveValueService: data dasar ditimpa revisi semua
    | putaran dengan putaran.id <= putaran target, lintas rekonsiliasi.
    |
    | Tanpa putaran (NULL) = derived data dasar, sehingga hanya
    | source data dasar yang dipakai.
    |
    */

    private function getEffectiveSource(
        $wilayahId,
        $periodeId,
        $jenisTabelId,
        $putaranId = null
    ): Collection {

        if ($putaranId !== null && !Putaran::whereKey($putaranId)->exists()) {
            throw new \InvalidArgumentException(
                'Putaran untuk perhitungan derived tidak ditemukan.'
            );
        }

        return app(EffectiveValueService::class)
            ->source(
                PdrbModul::LAPANGAN_USAHA,
                $wilayahId,
                [$periodeId],
                [$jenisTabelId],
                $putaranId === null ? 0 : (int) $putaranId
            )
            ->keyBy(function ($item) {
                return $this->kategoriKey(
                    $item->kategori_lapus_id
                );
            });
    }


    /*
    |--------------------------------------------------------------------------
    | HITUNG NILAI IMPLISIT EFEKTIF
    |--------------------------------------------------------------------------
    |
    | Digunakan juga untuk menghitung perubahan implisit
    | tanpa bergantung pada hasil derived putaran lain.
    |
    */

    private function getEffectiveImplicitValues(
        $wilayahId,
        $periodeId,
        $putaranId = null
    ): Collection {

        $adhb = $this->getEffectiveSource(
            $wilayahId,
            $periodeId,
            1,
            $putaranId
        );

        $adhk = $this->getEffectiveSource(
            $wilayahId,
            $periodeId,
            2,
            $putaranId
        );

        $result = collect();

        foreach ($adhb as $key => $item) {

            $dataAdhk = $adhk->get($key);

            if (!$dataAdhk) {
                continue;
            }

            $nilai = $dataAdhk->nilai == 0
                ? 0
                : ($item->nilai / $dataAdhk->nilai) * 100;

            $result[$key] = [
                'kategori_lapus_id' => $item->kategori_lapus_id,
                'nilai' => round($nilai, 6),
            ];
        }

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN HASIL DERIVED
    |--------------------------------------------------------------------------
    |
    | Penghapusan dibatasi pada jenis tabel, wilayah, periode,
    | dan putaran yang sedang dihitung.
    |
    */

    private function persistDerived(
        $wilayahId,
        $periodeId,
        $jenisTabelId,
        $putaranId,
        array $rows
    ): void {

        DB::transaction(function () use (
            $wilayahId,
            $periodeId,
            $jenisTabelId,
            $putaranId,
            $rows
        ) {

            $query = DataPdrbLapanganUsaha::where([
                'wilayah_id' => $wilayahId,
                'periode_id' => $periodeId,
                'jenis_tabel_id' => $jenisTabelId,
                'tipe_data' => 'derived',
            ]);

            if ($putaranId === null) {
                $query->whereNull('putaran_id');
            } else {
                $query->where('putaran_id', $putaranId);
            }

            $query->delete();

            if (!empty($rows)) {
                DataPdrbLapanganUsaha::insert($rows);
            }
        });
    }


    private function makeDerivedRow(
        $wilayahId,
        $periodeId,
        $jenisTabelId,
        $kategoriId,
        $nilai,
        $putaranId
    ): array {

        $now = now();

        return [
            'submission_id' => null,
            'putaran_id' => $putaranId,
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => $jenisTabelId,
            'kategori_lapus_id' => $kategoriId,
            'nilai' => round($nilai, 6),
            'tipe_data' => 'derived',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DISTRIBUSI
    |--------------------------------------------------------------------------
    */

    public function hitungDistribusi(
        $wilayahId,
        $periodeId,
        $putaranId = null
    ): void {

        $source = $this->getEffectiveSource(
            $wilayahId,
            $periodeId,
            1,
            $putaranId
        );

        if ($source->isEmpty()) {
            return;
        }

        $totalPdrb = $source
            ->filter(function ($item) {
                return $item->kategori
                    && $item->kategori->parent_id === null;
            })
            ->sum('nilai');

        if ($totalPdrb == 0) {
            return;
        }

        $rows = [];

        foreach ($source as $item) {

            $nilaiDistribusi =
                ($item->nilai / $totalPdrb) * 100;

            $rows[] = $this->makeDerivedRow(
                $wilayahId,
                $periodeId,
                3,
                $item->kategori_lapus_id,
                $nilaiDistribusi,
                $putaranId
            );
        }

        $this->persistDerived(
            $wilayahId,
            $periodeId,
            3,
            $putaranId,
            $rows
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PERIODE SEBELUMNYA UNTUK QTQ
    |--------------------------------------------------------------------------
    */

    private function getPeriodeQtQ($periodeId)
    {
        $periode = Periode::find($periodeId);

        if (!$periode) {
            return null;
        }

        if ($periode->triwulan == 1) {

            return Periode::where(
                'tahun',
                $periode->tahun - 1
            )
                ->where('triwulan', 4)
                ->first();
        }

        return Periode::where('tahun', $periode->tahun)
            ->where('triwulan', $periode->triwulan - 1)
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | QTQ
    |--------------------------------------------------------------------------
    */

    public function hitungQtQ(
        $wilayahId,
        $periodeId,
        $putaranId = null
    ): void {

        $periodeSebelumnya = $this->getPeriodeQtQ(
            $periodeId
        );

        if (!$periodeSebelumnya) {
            return;
        }

        $sourceSekarang = $this->getEffectiveSource(
            $wilayahId,
            $periodeId,
            2,
            $putaranId
        );

        if ($sourceSekarang->isEmpty()) {
            return;
        }

        $sourceSebelumnya = $this->getEffectiveSource(
            $wilayahId,
            $periodeSebelumnya->id,
            2,
            $putaranId
        );

        $rows = [];

        foreach ($sourceSekarang as $key => $item) {

            $sebelumnya = $sourceSebelumnya->get($key);

            if (!$sebelumnya || $sebelumnya->nilai == 0) {
                $qtq = 0;
            } else {
                $qtq = (
                    ($item->nilai / $sebelumnya->nilai) * 100
                ) - 100;
            }

            $rows[] = $this->makeDerivedRow(
                $wilayahId,
                $periodeId,
                4,
                $item->kategori_lapus_id,
                $qtq,
                $putaranId
            );
        }

        $this->persistDerived(
            $wilayahId,
            $periodeId,
            4,
            $putaranId,
            $rows
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PERIODE TAHUN SEBELUMNYA UNTUK YTY
    |--------------------------------------------------------------------------
    */

    private function getPeriodeYtY($periodeId)
    {
        $periode = Periode::find($periodeId);

        if (!$periode) {
            return null;
        }

        return Periode::where(
            'tahun',
            $periode->tahun - 1
        )
            ->where('triwulan', $periode->triwulan)
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | YTY
    |--------------------------------------------------------------------------
    */

    public function hitungYtY(
        $wilayahId,
        $periodeId,
        $putaranId = null
    ): void {

        $periodeSebelumnya = $this->getPeriodeYtY(
            $periodeId
        );

        if (!$periodeSebelumnya) {
            return;
        }

        $sourceSekarang = $this->getEffectiveSource(
            $wilayahId,
            $periodeId,
            2,
            $putaranId
        );

        if ($sourceSekarang->isEmpty()) {
            return;
        }

        $sourceTahunLalu = $this->getEffectiveSource(
            $wilayahId,
            $periodeSebelumnya->id,
            2,
            $putaranId
        );

        $rows = [];

        foreach ($sourceSekarang as $key => $item) {

            $tahunLalu = $sourceTahunLalu->get($key);

            if (!$tahunLalu || $tahunLalu->nilai == 0) {
                $yty = 0;
            } else {
                $yty = (
                    ($item->nilai / $tahunLalu->nilai) * 100
                ) - 100;
            }

            $rows[] = $this->makeDerivedRow(
                $wilayahId,
                $periodeId,
                5,
                $item->kategori_lapus_id,
                $yty,
                $putaranId
            );
        }

        $this->persistDerived(
            $wilayahId,
            $periodeId,
            5,
            $putaranId,
            $rows
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PERIODE KUMULATIF UNTUK CTC
    |--------------------------------------------------------------------------
    */

    private function getPeriodeCTC($tahun, $triwulan)
    {
        return Periode::where('tahun', $tahun)
            ->where('triwulan', '<=', $triwulan)
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | CTC
    |--------------------------------------------------------------------------
    */

    public function hitungCtC(
        $wilayahId,
        $periodeId,
        $putaranId = null
    ): void {

        $periode = Periode::find($periodeId);

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

        $sourceSekarang = [];
        $sourceTahunLalu = [];

        foreach ($periodeSekarang as $itemPeriode) {

            $source = $this->getEffectiveSource(
                $wilayahId,
                $itemPeriode->id,
                2,
                $putaranId
            );

            foreach ($source as $key => $item) {

                if (!isset($sourceSekarang[$key])) {
                    $sourceSekarang[$key] = [
                        'kategori_lapus_id' => $item->kategori_lapus_id,
                        'nilai' => 0,
                    ];
                }

                $sourceSekarang[$key]['nilai'] += $item->nilai;
            }
        }

        foreach ($periodeTahunLalu as $itemPeriode) {

            $source = $this->getEffectiveSource(
                $wilayahId,
                $itemPeriode->id,
                2,
                $putaranId
            );

            foreach ($source as $key => $item) {

                if (!isset($sourceTahunLalu[$key])) {
                    $sourceTahunLalu[$key] = [
                        'kategori_lapus_id' => $item->kategori_lapus_id,
                        'nilai' => 0,
                    ];
                }

                $sourceTahunLalu[$key]['nilai'] += $item->nilai;
            }
        }

        $rows = [];

        foreach ($sourceSekarang as $key => $dataSekarang) {

            $dataLalu = $sourceTahunLalu[$key] ?? null;

            if (!$dataLalu) {
                continue;
            }

            $totalSekarang = $dataSekarang['nilai'];
            $totalLalu = $dataLalu['nilai'];

            if ($totalLalu == 0) {
                $ctc = 0;
            } else {
                $ctc = (
                    ($totalSekarang / $totalLalu) * 100
                ) - 100;
            }

            $rows[] = $this->makeDerivedRow(
                $wilayahId,
                $periodeId,
                6,
                $dataSekarang['kategori_lapus_id'],
                $ctc,
                $putaranId
            );
        }

        $this->persistDerived(
            $wilayahId,
            $periodeId,
            6,
            $putaranId,
            $rows
        );
    }


    /*
    |--------------------------------------------------------------------------
    | IMPLISIT
    |--------------------------------------------------------------------------
    */

    public function hitungImplisit(
        $wilayahId,
        $periodeId,
        $putaranId = null
    ): void {

        $source = $this->getEffectiveImplicitValues(
            $wilayahId,
            $periodeId,
            $putaranId
        );

        if ($source->isEmpty()) {
            return;
        }

        $rows = [];

        foreach ($source as $item) {

            $rows[] = $this->makeDerivedRow(
                $wilayahId,
                $periodeId,
                7,
                $item['kategori_lapus_id'],
                $item['nilai'],
                $putaranId
            );
        }

        $this->persistDerived(
            $wilayahId,
            $periodeId,
            7,
            $putaranId,
            $rows
        );
    }


    /*
    |--------------------------------------------------------------------------
    | IMPLISIT QTQ
    |--------------------------------------------------------------------------
    */

    public function hitungImplisitQtQ(
        $wilayahId,
        $periodeId,
        $putaranId = null
    ): void {

        $periodeSebelumnya = $this->getPeriodeQtQ(
            $periodeId
        );

        if (!$periodeSebelumnya) {
            return;
        }

        $sourceSekarang = $this->getEffectiveImplicitValues(
            $wilayahId,
            $periodeId,
            $putaranId
        );

        if ($sourceSekarang->isEmpty()) {
            return;
        }

        $sourceSebelumnya = $this->getEffectiveImplicitValues(
            $wilayahId,
            $periodeSebelumnya->id,
            $putaranId
        );

        $rows = [];

        foreach ($sourceSekarang as $key => $item) {

            $sebelumnya = $sourceSebelumnya->get($key);

            if (!$sebelumnya || $sebelumnya['nilai'] == 0) {
                $qtq = 0;
            } else {
                $qtq = (
                    ($item['nilai'] / $sebelumnya['nilai']) * 100
                ) - 100;
            }

            $rows[] = $this->makeDerivedRow(
                $wilayahId,
                $periodeId,
                8,
                $item['kategori_lapus_id'],
                $qtq,
                $putaranId
            );
        }

        $this->persistDerived(
            $wilayahId,
            $periodeId,
            8,
            $putaranId,
            $rows
        );
    }


    /*
    |--------------------------------------------------------------------------
    | IMPLISIT YTY
    |--------------------------------------------------------------------------
    */

    public function hitungImplisitYtY(
        $wilayahId,
        $periodeId,
        $putaranId = null
    ): void {

        $periodeSebelumnya = $this->getPeriodeYtY(
            $periodeId
        );

        if (!$periodeSebelumnya) {
            return;
        }

        $sourceSekarang = $this->getEffectiveImplicitValues(
            $wilayahId,
            $periodeId,
            $putaranId
        );

        if ($sourceSekarang->isEmpty()) {
            return;
        }

        $sourceTahunLalu = $this->getEffectiveImplicitValues(
            $wilayahId,
            $periodeSebelumnya->id,
            $putaranId
        );

        $rows = [];

        foreach ($sourceSekarang as $key => $item) {

            $tahunLalu = $sourceTahunLalu->get($key);

            if (!$tahunLalu || $tahunLalu['nilai'] == 0) {
                $yty = 0;
            } else {
                $yty = (
                    ($item['nilai'] / $tahunLalu['nilai']) * 100
                ) - 100;
            }

            $rows[] = $this->makeDerivedRow(
                $wilayahId,
                $periodeId,
                9,
                $item['kategori_lapus_id'],
                $yty,
                $putaranId
            );
        }

        $this->persistDerived(
            $wilayahId,
            $periodeId,
            9,
            $putaranId,
            $rows
        );
    }
}

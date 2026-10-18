<?php

namespace App\Services\Pdrb;

use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| NILAI EFEKTIF
|--------------------------------------------------------------------------
|
| Nilai efektif sebuah sel pada putaran X:
|   data dasar (putaran_id NULL)
|   lalu ditimpa revisi semua putaran dengan putaran.id <= X,
|   berurutan menurut putaran.id, lintas rekonsiliasi.
|
| putaran.id mengikuti urutan waktu karena sistem hanya mengizinkan
| satu putaran berlangsung pada satu waktu.
|
| Aturan yang sama berlaku untuk source maupun derived.
|
| Hasil berupa Collection model, dengan kunci
| "wilayah:periode:jenis_tabel:kategori_key" (lihat kunci()).
|
*/

class EffectiveValueService
{
    /*
    | Nilai efektif pada putaran X (termasuk revisi putaran X).
    | $putaranId NULL = kondisi terbaru.
    */
    public function source(
        int $modulId,
        $wilayahIds,
        $periodeIds,
        $jenisTabelIds,
        ?int $putaranId = null
    ): Collection {

        return $this->ambil(
            $modulId,
            'source',
            $wilayahIds,
            $periodeIds,
            $jenisTabelIds,
            $putaranId,
            true
        );
    }


    /*
    | Nilai efektif sebelum putaran X (tanpa revisi putaran X).
    | Dipakai sebagai pembanding saat menyimpan revisi putaran X.
    */
    public function sourceSebelum(
        int $modulId,
        $wilayahIds,
        $periodeIds,
        $jenisTabelIds,
        int $putaranId
    ): Collection {

        return $this->ambil(
            $modulId,
            'source',
            $wilayahIds,
            $periodeIds,
            $jenisTabelIds,
            $putaranId,
            false
        );
    }


    public function derived(
        int $modulId,
        $wilayahIds,
        $periodeIds,
        $jenisTabelIds,
        ?int $putaranId = null
    ): Collection {

        return $this->ambil(
            $modulId,
            'derived',
            $wilayahIds,
            $periodeIds,
            $jenisTabelIds,
            $putaranId,
            true
        );
    }


    private bool $mentah = false;

    /*
    | Versi ringan: baris berupa stdClass (bukan model Eloquent) dengan kolom
    | seperlunya. Nilai efektifnya sama; untuk pembacaan massal (ekspor Excel).
    */
    public function mentah(): static
    {
        $salinan = clone $this;
        $salinan->mentah = true;

        return $salinan;
    }


    public static function kunci(
        $wilayahId,
        $periodeId,
        $jenisTabelId,
        $kategoriId
    ): string {

        return $wilayahId . ':'
            . $periodeId . ':'
            . $jenisTabelId . ':'
            . PdrbModul::kategoriKey($kategoriId);
    }


    private function ambil(
        int $modulId,
        string $tipeData,
        $wilayahIds,
        $periodeIds,
        $jenisTabelIds,
        ?int $putaranId,
        bool $termasukPutaran
    ): Collection {

        $model = PdrbModul::model($modulId);
        $kategoriColumn = PdrbModul::kategoriColumn($modulId);

        $query = $model::query()
            ->where('tipe_data', $tipeData)
            ->whereIn('wilayah_id', (array) $wilayahIds)
            ->whereIn('periode_id', (array) $periodeIds)
            ->whereIn('jenis_tabel_id', (array) $jenisTabelIds)
            ->when($putaranId !== null, function ($query) use (
                $putaranId,
                $termasukPutaran
            ) {
                $query->where(function ($q) use (
                    $putaranId,
                    $termasukPutaran
                ) {
                    $q->whereNull('putaran_id')
                        ->orWhere(
                            'putaran_id',
                            $termasukPutaran ? '<=' : '<',
                            $putaranId
                        );
                });
            })
            // Data dasar (versi_key 0) lebih dulu, lalu revisi menurut
            // putaran.id; baris terakhir per sel menjadi nilai efektif.
            ->orderBy('versi_key')
            ->orderBy('id');

        $rows = $this->mentah
            ? $query->toBase()->get([
                'wilayah_id', 'periode_id', 'jenis_tabel_id',
                $kategoriColumn, 'nilai', 'putaran_id',
            ])
            : $query->get();

        $effective = collect();

        foreach ($rows as $row) {

            $effective[self::kunci(
                $row->wilayah_id,
                $row->periode_id,
                $row->jenis_tabel_id,
                $row->{$kategoriColumn}
            )] = $row;
        }

        return $effective;
    }
}

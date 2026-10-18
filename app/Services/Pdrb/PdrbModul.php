<?php

namespace App\Services\Pdrb;

use App\Models\DataPdrbLapanganUsaha;
use App\Models\DataPdrbPengeluaran;

/*
|--------------------------------------------------------------------------
| PERBEDAAN STRUKTUR ANTARMODUL
|--------------------------------------------------------------------------
|
| Lapangan Usaha: TOTAL disimpan dengan kategori_lapus_id = NULL,
| sehingga identitas kategori memakai kolom virtual kategori_key
| (COALESCE(kategori_lapus_id, 0)).
|
| Pengeluaran: kategori_pengeluaran_id NOT NULL.
|
*/

class PdrbModul
{
    const LAPANGAN_USAHA = 1;
    const PENGELUARAN = 2;

    const TOTAL_KEY = 0;


    public static function model(int $modulId): string
    {
        return match ($modulId) {
            self::LAPANGAN_USAHA => DataPdrbLapanganUsaha::class,
            self::PENGELUARAN => DataPdrbPengeluaran::class,
            default => throw new \InvalidArgumentException(
                "Modul {$modulId} tidak dikenal."
            ),
        };
    }


    /*
    | Kolom kategori asli (bisa NULL untuk TOTAL Lapangan Usaha).
    */
    public static function kategoriColumn(int $modulId): string
    {
        return match ($modulId) {
            self::LAPANGAN_USAHA => 'kategori_lapus_id',
            self::PENGELUARAN => 'kategori_pengeluaran_id',
            default => throw new \InvalidArgumentException(
                "Modul {$modulId} tidak dikenal."
            ),
        };
    }


    /*
    | Kolom kategori yang aman untuk whereIn dan UNIQUE INDEX.
    */
    public static function kategoriKeyColumn(int $modulId): string
    {
        return match ($modulId) {
            self::LAPANGAN_USAHA => 'kategori_key',
            self::PENGELUARAN => 'kategori_pengeluaran_id',
            default => throw new \InvalidArgumentException(
                "Modul {$modulId} tidak dikenal."
            ),
        };
    }


    public static function kategoriKey($kategoriId): int
    {
        return $kategoriId === null
            ? self::TOTAL_KEY
            : (int) $kategoriId;
    }


    /*
    | Kolom UNIQUE INDEX tabel data, dipakai untuk upsert.
    */
    public static function uniqueColumns(int $modulId): array
    {
        return [
            'wilayah_id',
            'periode_id',
            'jenis_tabel_id',
            self::kategoriKeyColumn($modulId),
            'tipe_data',
            'versi_key',
        ];
    }
}

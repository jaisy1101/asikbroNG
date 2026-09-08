<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use Illuminate\Support\Facades\File;
use App\Models\Wilayah;
use App\Services\LapanganUsaha\LapanganUsahaImportService;
use App\Services\Pengeluaran\PengeluaranImportService;

#[Signature('import:data-dasar')]
#[Description('Import data dasar PDRB historis')]
class ImportDataDasar extends Command
{

    public function handle(
        LapanganUsahaImportService $lapanganUsahaImport,
        PengeluaranImportService $pengeluaranImport
    )
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        /*
        |--------------------------------------------------------------------------
        | DATA DASAR LAPANGAN USAHA
        |--------------------------------------------------------------------------

        $folderLapangan = storage_path('app/data-dasar/Lapangan_Usaha');

        $files = File::files($folderLapangan);

        foreach ($files as $file) {

            $namaFile = $file->getFilename();


            // ambil kode BPS dari nama file
            preg_match('/\d{4}/', $namaFile, $match);


            if (!$match) {
                $this->error("Kode wilayah tidak ditemukan: ".$namaFile);
                continue;
            }


            $kodeBps = $match[0];


            $wilayah = Wilayah::where(
                'kode_bps',
                $kodeBps
            )->first();


            if (!$wilayah) {
                $this->error("Wilayah tidak ditemukan: ".$kodeBps);
                continue;
            }


            $this->info(
                "Import Lapangan Usaha : ".$wilayah->nama
            );


            $lapanganUsahaImport->import(
                $file->getPathname(),
                null,
                $wilayah->id
            );

            gc_collect_cycles();

        }
        */
        unset($files);
        gc_collect_cycles();

        /*
        |--------------------------------------------------------------------------
        | DATA DASAR PENGELUARAN
        |--------------------------------------------------------------------------
        */

        $folderPengeluaran = storage_path('app/data-dasar/Pengeluaran');

        $files = File::files($folderPengeluaran);


        foreach ($files as $file) {


            $namaFile = $file->getFilename();


            preg_match('/\d{4}/', $namaFile, $match);


            if (!$match) {
                $this->error("Kode wilayah tidak ditemukan: ".$namaFile);
                continue;
            }


            $kodeBps = $match[0];


            $wilayah = Wilayah::where(
                'kode_bps',
                $kodeBps
            )->first();


            if (!$wilayah) {
                $this->error("Wilayah tidak ditemukan: ".$kodeBps);
                continue;
            }


            $this->info(
                "Import Pengeluaran : ".$wilayah->nama
            );


            $pengeluaranImport->import(
                $file->getPathname(),
                null,
                $wilayah->id
            );

            gc_collect_cycles();

        }

        /*
        |--------------------------------------------------------------------------
        | DATA DASAR PROVINSI
        |--------------------------------------------------------------------------
        */

        $folderProvinsi = storage_path('app/data-dasar/Provinsi');

        $files = File::files($folderProvinsi);


        foreach ($files as $file) {

            $namaFile = $file->getFilename();

            preg_match('/\d{4}/', $namaFile, $match);


            if (!$match) {
                continue;
            }


            $kodeFile = $match[0];

            if ($kodeFile == '7300') {
                $kodeBps = '73';
            }


            $wilayah = Wilayah::where(
                'kode_bps',
                $kodeBps
            )->first();


            if (!$wilayah) {
                $this->error("Provinsi tidak ditemukan: ".$kodeBps);
                continue;
            }


            $this->info(
                "Import Provinsi : ".$wilayah->nama
            );


            if (str_contains($namaFile, 'Lapangan')) {

                $lapanganUsahaImport->import(
                    $file->getPathname(),
                    null,
                    $wilayah->id
                );

            }


            if (str_contains($namaFile, 'Pengeluaran')) {

                $pengeluaranImport->import(
                    $file->getPathname(),
                    null,
                    $wilayah->id
                );

            }


            gc_collect_cycles();

        }


        $this->info("Import data dasar selesai.");

    }
}
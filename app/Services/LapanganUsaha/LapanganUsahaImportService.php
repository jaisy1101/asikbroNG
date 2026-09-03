<?php

namespace App\Services\LapanganUsaha;

use App\Models\DataPdrbLapanganUsaha;
use App\Models\Periode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Models\RekonsiliasiPeriode;

class LapanganUsahaImportService
{
    public function import($filePath, $submission=null, $wilayahId=null)
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($filePath);

        $sheet = $spreadsheet->getActiveSheet();


        // Tabel 1 ADHB
        $this->importTable(
            $sheet,
            4,
            5,
            9,
            73,
            1,
            $submission,
            $wilayahId
        );


        // Tabel 2 ADHK
        $this->importTable(
            $sheet,
            81,     // baris tahun
            82,     // baris triwulan
            86,     // mulai kategori
            150,    // akhir kategori
            2,      // jenis tabel ADHK
            $submission,
            $wilayahId
        );
    }



    private function importTable(
        $sheet,
        $tahunRow,
        $triwulanRow,
        $startRow,
        $endRow,
        $jenisTabelId,
        $submission,
        $wilayahId
    ) {

        $highestColumn = $sheet->getHighestColumn();

        $tahunAktif = null;


        $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString('D');

        $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);


        for ($col = $startColumn; $col <= $endColumn; $col++) {


            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);


            // ambil tahun
            $tahunCell = $sheet
                ->getCell($column . $tahunRow)
                ->getCalculatedValue();


            if ($tahunCell != null) {
                $tahunAktif = $tahunCell;
            }



            // ambil triwulan
            $triwulan = $sheet
                ->getCell($column . $triwulanRow)
                ->getCalculatedValue();



            if (!$tahunAktif || !$triwulan) {
                continue;
            }



            // skip kolom Total
            if (strtolower(trim($triwulan)) == 'total') {
                continue;
            }



            $triwulanAngka = $this->convertTriwulan($triwulan);



            if (!$triwulanAngka) {
                continue;
            }



            $periode = Periode::where('tahun', $tahunAktif)
                ->where('triwulan', $triwulanAngka)
                ->first();



            if (!$periode) {
                continue;
            }

            if ($submission) {

                $periodeAllowed = RekonsiliasiPeriode::whereHas(
                    'rekonsiliasi.putaran',
                    function ($query) {

                        $query->where('status', 'berlangsung');

                    }
                )
                ->where('periode_id', $periode->id)
                ->exists();


                if (!$periodeAllowed) {
                    continue;
                }

            }


            $kategoriId = 1;


            for ($row = $startRow; $row <= $endRow; $row++) {


                $nilai = $sheet
                    ->getCell($column . $row)
                    ->getCalculatedValue();



                if ($nilai === null || $nilai === '') {
                    $kategoriId++;
                    continue;
                }



                DataPdrbLapanganUsaha::create([

                    'submission_id' => $submission ? $submission->id : null,

                    'wilayah_id' => $submission ? $submission->wilayah_id : $wilayahId,

                    'periode_id' => $periode->id,

                    'jenis_tabel_id' => $jenisTabelId,

                    'kategori_lapus_id' => $kategoriId,

                    'nilai' => $nilai,

                    'tipe_data' => 'source',

                ]);


                $kategoriId++;

            }

        }

    }



    private function convertTriwulan($value)
    {

        return match(strtoupper(trim($value))) {

            'I' => 1,

            'II' => 2,

            'III' => 3,

            'IV' => 4,

            default => null,

        };

    }
}
<?php

namespace App\Services\Pengeluaran;

use App\Models\DataPdrbPengeluaran;
use App\Models\Periode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PengeluaranImportService
{

    public function import($filePath, $submission)
    {
        $spreadsheet = IOFactory::load($filePath);

        $sheet = $spreadsheet->getActiveSheet();


        // Tabel 1 ADHB
        $this->importTable(
            $sheet,
            4,
            9,
            33,
            1,
            $submission
        );


        // Tabel 2 ADHK
        $this->importTable(
            $sheet,
            81,
            86,
            110,
            2,
            $submission
        );
    }



    private function importTable(
        $sheet,
        $headerRow,
        $startRow,
        $endRow,
        $jenisTabelId,
        $submission
    )
    {

        $highestColumn = $sheet->getHighestColumn();


        $startColumn = Coordinate::columnIndexFromString('D');

        $endColumn = Coordinate::columnIndexFromString($highestColumn);



        for ($col = $startColumn; $col <= $endColumn; $col++) {


            $column = Coordinate::stringFromColumnIndex($col);



            // Header contoh:
            // I-2018
            // II-2018
            // Total-2018

            $header = $sheet
                ->getCell($column . $headerRow)
                ->getValue();



            if (!$header) {
                continue;
            }



            // Skip Total
            if (str_contains(strtolower($header), 'total')) {
                continue;
            }



            $periodeData = $this->parsePeriode($header);



            if (!$periodeData) {
                continue;
            }



            $periode = Periode::where('tahun', $periodeData['tahun'])
                ->where('triwulan', $periodeData['triwulan'])
                ->first();



            if (!$periode) {
                continue;
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



                DataPdrbPengeluaran::create([

                    'submission_id' => $submission->id,

                    'wilayah_id' => $submission->wilayah_id,

                    'periode_id' => $periode->id,

                    'jenis_tabel_id' => $jenisTabelId,

                    'kategori_pengeluaran_id' => $kategoriId,

                    'nilai' => $nilai,

                    'tipe_data' => 'source',

                ]);



                $kategoriId++;

            }

        }

    }



    private function parsePeriode($header)
    {

        // contoh:
        // I-2018


        $parts = explode('-', $header);



        if (count($parts) != 2) {
            return null;
        }



        $triwulan = match(trim($parts[0])) {

            'I' => 1,
            'II' => 2,
            'III' => 3,
            'IV' => 4,

            default => null

        };



        $tahun = intval($parts[1]);



        if (!$triwulan || !$tahun) {
            return null;
        }



        return [

            'tahun' => $tahun,

            'triwulan' => $triwulan,

        ];

    }

}
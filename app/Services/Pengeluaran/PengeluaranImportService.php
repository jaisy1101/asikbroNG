<?php

namespace App\Services\Pengeluaran;

use App\Models\DataPdrbPengeluaran;
use App\Models\Periode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Models\RekonsiliasiPeriode;

class PengeluaranImportService
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
            9,
            33,
            1,
            $submission,
            $wilayahId
        );


        // Tabel 2 ADHK
        $this->importTable(
            $sheet,
            81,
            86,
            110,
            2,
            $submission,
            $wilayahId
        );
    }



    private function importTable(
        $sheet,
        $headerRow,
        $startRow,
        $endRow,
        $jenisTabelId,
        $submission,
        $wilayahId
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



                DataPdrbPengeluaran::create([

                    'submission_id' => $submission ? $submission->id : null,

                    'wilayah_id' => $submission ? $submission->wilayah_id : $wilayahId,

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
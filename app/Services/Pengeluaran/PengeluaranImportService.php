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

        $allowedPeriods = [];

        if ($submission) {

            $allowedPeriods = RekonsiliasiPeriode::where(
                'rekonsiliasi_id',
                $submission->putaran->rekonsiliasi_id
            )
            ->with('periode')
            ->get()
            ->mapWithKeys(function($item){

                return [
                    $item->periode->tahun . '-' . $item->periode->triwulan => true
                ];

            })
            ->toArray();

        }


        // Tabel 1 ADHB
        $this->importTable(
            $sheet,
            4,
            9,
            33,
            1,
            $submission,
            $wilayahId,
            $allowedPeriods
        );


        // Tabel 2 ADHK
        $this->importTable(
            $sheet,
            81,
            86,
            110,
            2,
            $submission,
            $wilayahId,
            $allowedPeriods
        );
    }



    private function importTable(
        $sheet,
        $headerRow,
        $startRow,
        $endRow,
        $jenisTabelId,
        $submission,
        $wilayahId,
        $allowedPeriods
    )
    {

        $highestColumn = $sheet->getHighestColumn();


        $startColumn = Coordinate::columnIndexFromString('D');

        $endColumn = Coordinate::columnIndexFromString($highestColumn);



        /*
        |--------------------------------------------------------------------------
        | Cari kolom periode yang dibutuhkan
        |--------------------------------------------------------------------------
        */

        $columns = [];



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



            if (str_contains(strtolower($header), 'total')) {
                continue;
            }



            $periodeData = $this->parsePeriode($header);



            if (!$periodeData) {
                continue;
            }



            if ($submission) {

                $key = $periodeData['tahun'] . '-' . $periodeData['triwulan'];


                if (!isset($allowedPeriods[$key])) {
                    continue;
                }

            }



            $columns[] = [

                'column' => $column,

                'tahun' => $periodeData['tahun'],

                'triwulan' => $periodeData['triwulan'],

            ];


        }




        /*
        |--------------------------------------------------------------------------
        | Ambil periode sekali
        |--------------------------------------------------------------------------
        */


        $periodeIds = Periode::whereIn(
            'tahun',
            collect($columns)
                ->pluck('tahun')
                ->unique()
        )
        ->get()
        ->keyBy(function($item){

            return $item->tahun . '-' . $item->triwulan;

        });




        $rows = [];




        /*
        |--------------------------------------------------------------------------
        | Baca hanya kolom yang diperlukan
        |--------------------------------------------------------------------------
        */

        foreach ($columns as $colData) {


            $key = $colData['tahun'] . '-' . $colData['triwulan'];



            if (!isset($periodeIds[$key])) {
                continue;
            }



            $periode = $periodeIds[$key];



            $kategoriId = 1;



            for ($row = $startRow; $row <= $endRow; $row++) {



                $nilai = $sheet
                    ->getCell($colData['column'] . $row)
                    ->getCalculatedValue();



                if ($nilai === null || $nilai === '') {

                    $nilai = 0;

                }




                $rows[] = [

                    'submission_id' => $submission ? $submission->id : null,

                    'wilayah_id' => $submission ? $submission->wilayah_id : $wilayahId,

                    'periode_id' => $periode->id,

                    'jenis_tabel_id' => $jenisTabelId,

                    'kategori_pengeluaran_id' => $kategoriId,

                    'nilai' => $nilai,

                    'tipe_data' => 'source',

                    'created_at' => now(),

                    'updated_at' => now(),

                ];



                $kategoriId++;

            }

        }




        /*
        |--------------------------------------------------------------------------
        | Insert sekali
        |--------------------------------------------------------------------------
        */

        if (count($rows) > 0) {

            DataPdrbPengeluaran::insert($rows);

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
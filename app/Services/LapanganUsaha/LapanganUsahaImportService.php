<?php

namespace App\Services\LapanganUsaha;

use App\Models\DataPdrbLapanganUsaha;
use App\Models\Periode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Models\RekonsiliasiPeriode;
use illuminate\Support\Facades\DB;


class LapanganUsahaImportService
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
            5,
            9,
            73,
            1,
            $submission,
            $wilayahId,
            $allowedPeriods
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
            $wilayahId,
            $allowedPeriods
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
        $wilayahId,
        $allowedPeriods
    ) {

        $highestColumn = $sheet->getHighestColumn();

        $startColumn = Coordinate::columnIndexFromString('D');

        $endColumn = Coordinate::columnIndexFromString($highestColumn);


        /*
        |--------------------------------------------------------------------------
        | Cari kolom yang dibutuhkan saja
        |--------------------------------------------------------------------------
        */

        $columns = [];

        $tahunAktif = null;


        for ($col = $startColumn; $col <= $endColumn; $col++) {


            $column = Coordinate::stringFromColumnIndex($col);


            $tahunCell = $sheet
                ->getCell($column . $tahunRow)
                ->getCalculatedValue();


            if ($tahunCell != null) {
                $tahunAktif = $tahunCell;
            }


            $triwulan = $sheet
                ->getCell($column . $triwulanRow)
                ->getCalculatedValue();



            if (!$tahunAktif || !$triwulan) {
                continue;
            }


            if (strtolower(trim($triwulan)) == 'total') {
                continue;
            }


            $triwulanAngka = $this->convertTriwulan($triwulan);


            if (!$triwulanAngka) {
                continue;
            }



            /*
            | hanya ambil periode rekon
            */

            if ($submission) {

                $key = $tahunAktif . '-' . $triwulanAngka;


                if (!isset($allowedPeriods[$key])) {
                    continue;
                }

            }


            $columns[] = [

                'column' => $column,

                'tahun' => $tahunAktif,

                'triwulan' => $triwulanAngka,

            ];

        }



        /*
        |--------------------------------------------------------------------------
        | Ambil periode sekali
        |--------------------------------------------------------------------------
        */

        $periodeIds = Periode::whereIn('tahun',
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
        | Baca hanya kolom yang dibutuhkan
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

                    'kategori_lapus_id' => $kategoriId,

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

            DataPdrbLapanganUsaha::insert($rows);

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
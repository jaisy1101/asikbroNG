<?php

namespace App\Services\LapanganUsaha;

use App\Models\DataPdrbLapanganUsaha;
use App\Models\Periode;
use App\Models\RekonsiliasiPeriode;
use App\Services\Pdrb\SourceWriter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LapanganUsahaImportService
{
    public function __construct(
        private SourceWriter $writer
    ) {
    }

    /*
    | Dengan submission: mengembalikan
    |   ['jumlah_perubahan' => int, 'periode_berubah' => int[]]
    | Tanpa submission (data dasar): mengembalikan hasil kosong.
    */
    public function import($filePath, $submission = null, $wilayahId = null)
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($filePath);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $allowedPeriods = [];

            if ($submission) {
                $allowedPeriods = RekonsiliasiPeriode::where(
                    'rekonsiliasi_id',
                    $submission->putaran->rekonsiliasi_id
                )
                    ->with('periode')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [
                            $item->periode->tahun . '-' . $item->periode->triwulan => true,
                        ];
                    })
                    ->toArray();
            }

            // Tabel 1 ADHB
            $hasilAdhb = $this->importTable($sheet, 4, 5, 9, 73, 1, $submission, $wilayahId, $allowedPeriods);

            // Tabel 2 ADHK
            $hasilAdhk = $this->importTable($sheet, 81, 82, 86, 150, 2, $submission, $wilayahId, $allowedPeriods);

            return SourceWriter::gabungHasil($hasilAdhb, $hasilAdhk);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();
        }
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

        $columns = [];
        $tahunAktif = null;

        for ($col = $startColumn; $col <= $endColumn; $col++) {
            $column = Coordinate::stringFromColumnIndex($col);
            $tahunCell = $sheet->getCell($column . $tahunRow)->getCalculatedValue();

            if ($tahunCell !== null && $tahunCell !== '') {
                $tahunAktif = $tahunCell;
            }

            $triwulan = $sheet->getCell($column . $triwulanRow)->getCalculatedValue();

            if (!$tahunAktif || !$triwulan) {
                continue;
            }

            if (strtolower(trim((string) $triwulan)) === 'total') {
                continue;
            }

            $triwulanAngka = $this->convertTriwulan($triwulan);
            if (!$triwulanAngka) {
                continue;
            }

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

        if (empty($columns)) {
            return null;
        }

        $periodeIds = Periode::whereIn(
            'tahun',
            collect($columns)->pluck('tahun')->unique()
        )->get()->keyBy(function ($item) {
            return $item->tahun . '-' . $item->triwulan;
        });

        // Kumpulkan nilai Excel (layout dan konversi nilai mengikuti service lama).
        $rows = [];
        $now = now();

        foreach ($columns as $colData) {
            $key = $colData['tahun'] . '-' . $colData['triwulan'];
            if (!isset($periodeIds[$key])) {
                continue;
            }

            $periode = $periodeIds[$key];
            $kategoriId = 1;

            for ($row = $startRow; $row <= $endRow; $row++) {
                $nilai = $sheet->getCell($colData['column'] . $row)->getCalculatedValue();

                // PERHATIAN: Ini mempertahankan aturan lama: sel kosong -> 0.
                // Hanya aman jika file Excel merupakan tabel lengkap, bukan patch parsial.
                if ($nilai === null || $nilai === '' || !is_numeric($nilai)) {
                    $nilai = 0;
                }

                $rows[] = [
                    'submission_id' => $submission ? $submission->id : null,
                    'putaran_id' => $submission ? $submission->putaran_id : null,
                    'wilayah_id' => $submission ? $submission->wilayah_id : $wilayahId,
                    'periode_id' => $periode->id,
                    'jenis_tabel_id' => $jenisTabelId,
                    'kategori_lapus_id' => $kategoriId,
                    'nilai' => $nilai,
                    'tipe_data' => 'source',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $kategoriId++;
            }
        }

        if (empty($rows)) {
            return null;
        }

        if (!$submission) {
            // Data dasar tidak boleh tertimpa.
            DataPdrbLapanganUsaha::insertOrIgnore($rows);
            return null;
        }

        // Revisi putaran: aturan simpan mengikuti SourceWriter
        // (sama dengan input form).
        return $this->writer->simpan(
            $submission,
            $jenisTabelId,
            array_map(fn ($row) => [
                'periode_id' => $row['periode_id'],
                'kategori_id' => $row['kategori_lapus_id'],
                'nilai' => $row['nilai'],
            ], $rows)
        );
    }

    private function convertTriwulan($value)
    {
        return match (strtoupper(trim((string) $value))) {
            'I' => 1,
            'II' => 2,
            'III' => 3,
            'IV' => 4,
            default => null,
        };
    }
}

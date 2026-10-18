<?php

namespace App\Services\Pengeluaran;

use App\Models\DataPdrbPengeluaran;
use App\Models\Periode;
use App\Models\RekonsiliasiPeriode;
use App\Services\Pdrb\SourceWriter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PengeluaranImportService
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
                $putaran = $submission->putaran;

                if (!$putaran) {
                    throw new \RuntimeException('Putaran submission tidak ditemukan.');
                }

                $allowedPeriods = RekonsiliasiPeriode::where(
                    'rekonsiliasi_id',
                    $putaran->rekonsiliasi_id
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

            // ADHB
            $hasilAdhb = $this->importTable(
                $sheet, 4, 9, 33, 1,
                $submission, $wilayahId, $allowedPeriods
            );

            // ADHK
            $hasilAdhk = $this->importTable(
                $sheet, 81, 86, 110, 2,
                $submission, $wilayahId, $allowedPeriods
            );

            return SourceWriter::gabungHasil($hasilAdhb, $hasilAdhk);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();
        }
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
    ) {
        $endColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $startColumn = Coordinate::columnIndexFromString('D');
        $columns = [];

        for ($col = $startColumn; $col <= $endColumn; $col++) {
            $column = Coordinate::stringFromColumnIndex($col);
            $header = $sheet->getCell($column . $headerRow)->getValue();

            if (!$header || str_contains(strtolower((string) $header), 'total')) {
                continue;
            }

            $periodeData = $this->parsePeriode((string) $header);
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

        $periodeIds = Periode::whereIn('tahun', collect($columns)->pluck('tahun')->unique())
            ->get()
            ->keyBy(fn ($item) => $item->tahun . '-' . $item->triwulan);

        $targetWilayahId = $submission ? $submission->wilayah_id : $wilayahId;
        $rows = [];

        foreach ($columns as $colData) {
            $key = $colData['tahun'] . '-' . $colData['triwulan'];
            if (!isset($periodeIds[$key])) {
                continue;
            }

            $periodeId = $periodeIds[$key]->id;

            $kategoriId = 1;
            for ($row = $startRow; $row <= $endRow; $row++, $kategoriId++) {
                $nilai = $sheet->getCell($colData['column'] . $row)->getCalculatedValue();

                // Menjaga perilaku template Excel lama: sel kosong/bukan angka menjadi nol.
                if ($nilai === null || $nilai === '' || !is_numeric($nilai)) {
                    $nilai = 0;
                }

                $rows[] = [
                    'submission_id' => null,
                    'putaran_id' => null,
                    'wilayah_id' => $targetWilayahId,
                    'periode_id' => $periodeId,
                    'jenis_tabel_id' => $jenisTabelId,
                    'kategori_pengeluaran_id' => $kategoriId,
                    'nilai' => $nilai,
                    'tipe_data' => 'source',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (empty($rows)) {
            return null;
        }

        if (!$submission) {
            // Data dasar tidak boleh tertimpa.
            DataPdrbPengeluaran::insertOrIgnore($rows);
            return null;
        }

        // Revisi putaran: aturan simpan mengikuti SourceWriter
        // (sama dengan input form).
        return $this->writer->simpan(
            $submission,
            $jenisTabelId,
            array_map(fn ($row) => [
                'periode_id' => $row['periode_id'],
                'kategori_id' => $row['kategori_pengeluaran_id'],
                'nilai' => $row['nilai'],
            ], $rows)
        );
    }

    private function parsePeriode($header)
    {
        $parts = explode('-', $header);
        if (count($parts) !== 2) {
            return null;
        }

        $triwulan = match (trim($parts[0])) {
            'I' => 1,
            'II' => 2,
            'III' => 3,
            'IV' => 4,
            default => null,
        };

        $tahun = intval($parts[1]);
        if (!$triwulan || !$tahun) {
            return null;
        }

        return ['tahun' => $tahun, 'triwulan' => $triwulan];
    }
}

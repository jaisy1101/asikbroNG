<?php

namespace App\Services\Pdrb;

use App\Models\KategoriLapanganUsaha;
use App\Models\KategoriPengeluaran;
use App\Models\Periode;
use App\Models\Putaran;
use App\Models\RekonsiliasiPeriode;
use App\Models\Wilayah;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/*
|--------------------------------------------------------------------------
| EXCEL PDRB DARI NILAI EFEKTIF
|--------------------------------------------------------------------------
|
| Tata letak meniru file data dasar (dibaca LapanganUsahaImportService /
| PengeluaranImportService):
|   - setiap tabel 77 baris; tabel ke-k dimulai di baris 77k + 1
|   - judul baris +1, wilayah baris +2, header +4 (LU: tahun +4, triwulan +5;
|     Pengeluaran: "I-2025" di +4), data mulai +9 berurutan menurut id kategori
|   - kolom label A-C, kolom periode mulai D
|
| Mode:
|   template : tabel ADHB/ADHK, hanya periode terbuka, tanpa kolom Total
|              (diunduh user untuk diisi lalu diunggah kembali)
|   lengkap  : 9 tabel, seri penuh sampai triwulan rekonsiliasi, kolom
|              Total tahunan dan baris PDRB (ekspor admin)
|
| Seluruh nilai berupa angka (bukan rumus).
|
*/

class PdrbExcelExport
{
    private const BARIS_PER_TABEL = 77;
    private const BARIS_DATA = 9;
    private const BARIS_PDRB = 74;
    private const KOLOM_AWAL = 4; // D

    private const TAHUN_AWAL = [
        PdrbModul::LAPANGAN_USAHA => 2010,
        PdrbModul::PENGELUARAN => 2018,
    ];

    private const JUDUL_TABEL = [
        1 => ['PDRB SERI 2010 ATAS DASAR HARGA BERLAKU', 'JUTA RUPIAH'],
        2 => ['PDRB SERI 2010 ATAS DASAR HARGA KONSTAN', 'JUTA RUPIAH'],
        3 => ['DISTRIBUSI PERSENTASE PDRB SERI 2010', 'PERSEN'],
        4 => ['LAJU PERTUMBUHAN Q-to-Q PDRB SERI 2010', 'PERSEN'],
        5 => ['LAJU PERTUMBUHAN Y-on-Y PDRB SERI 2010', 'PERSEN'],
        6 => ['LAJU PERTUMBUHAN C-to-C PDRB SERI 2010', 'PERSEN'],
        7 => ['INDEKS IMPLISIT PDRB SERI 2010', 'PERSEN'],
        8 => ['LAJU IMPLISIT Q-to-Q PDRB SERI 2010', 'PERSEN'],
        9 => ['LAJU IMPLISIT Y-on-Y PDRB SERI 2010', 'PERSEN'],
    ];

    // LU: PDRB tanpa migas = PDRB - Pertambangan Migas - Industri Pengilangan Migas.
    private const KATEGORI_MIGAS = [13, 20];

    private const ROMAWI = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];


    public function __construct(
        private EffectiveValueService $effective
    ) {
    }


    public function buat(int $modulId, Wilayah $wilayah, Putaran $putaran, bool $lengkap): Spreadsheet
    {
        $putaran->loadMissing('rekonsiliasi.periode');

        $kolom = $lengkap
            ? $this->kolomLengkap($modulId, $putaran->rekonsiliasi->periode)
            : $this->kolomTemplate($putaran);

        $periodeIds = collect($kolom)->where('tipe', 'periode')->pluck('periode.id')->all();

        $jenisTabel = $lengkap ? array_keys(self::JUDUL_TABEL) : [1, 2];

        // Baris mentah (tanpa model Eloquent): puluhan ribu sel pada ekspor lengkap.
        $effective = $this->effective->mentah();

        $sumber = $effective->source($modulId, $wilayah->id, $periodeIds, [1, 2], $putaran->id);

        $derived = $lengkap
            ? $effective->derived($modulId, $wilayah->id, $periodeIds, array_slice($jenisTabel, 2), $putaran->id)
            : collect();

        $nilai = $sumber->merge($derived);

        $kategori = $this->kategori($modulId);

        $book = new Spreadsheet();

        // Format angka untuk seluruh sel sekaligus (lebih cepat dari per rentang);
        // header tahun diberi format sendiri.
        $book->getDefaultStyle()->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet = $book->getActiveSheet();
        $sheet->setTitle($wilayah->kodeBerkas());

        foreach ($jenisTabel as $index => $jenisTabelId) {

            $this->tulisTabel(
                $sheet,
                self::BARIS_PER_TABEL * $index,
                $modulId,
                $jenisTabelId,
                $wilayah,
                $putaran,
                $kolom,
                $kategori,
                $nilai,
                $lengkap
            );
        }

        $sheet->getColumnDimension('A')->setWidth(8.5);
        $sheet->getColumnDimension('B')->setWidth($modulId === PdrbModul::LAPANGAN_USAHA ? 4.5 : 3);
        $sheet->getColumnDimension('C')->setWidth($modulId === PdrbModul::LAPANGAN_USAHA ? 34 : 39);

        for ($i = 0; $i < count($kolom); $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::KOLOM_AWAL + $i))->setWidth(14);
        }

        $sheet->freezePane('D' . self::BARIS_DATA);

        return $book;
    }


    public function namaFile(int $modulId, Wilayah $wilayah, Putaran $putaran): string
    {
        $putaran->loadMissing('rekonsiliasi.periode');

        $periode = $putaran->rekonsiliasi->periode;

        $modul = $modulId === PdrbModul::LAPANGAN_USAHA ? 'Lapangan_Usaha' : 'Pengeluaran';

        return sprintf(
            '%s_%s_%dQ%d_Putaran%d.xlsx',
            $wilayah->kodeBerkas(),
            $modul,
            $periode->tahun,
            $periode->triwulan,
            $putaran->nomor
        );
    }


    /*
    | Menulis ke berkas sementara; pemanggil mengirim lalu menghapusnya.
    */
    public function simpanSementara(Spreadsheet $book): string
    {
        $dasar = tempnam(sys_get_temp_dir(), 'pdrb');
        @unlink($dasar);

        $path = $dasar . '.xlsx';

        $writer = new Xlsx($book);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);

        $book->disconnectWorksheets();

        return $path;
    }


    /*
    |--------------------------------------------------------------------------
    | KOLOM
    |--------------------------------------------------------------------------
    | Setiap kolom: ['tipe' => 'periode', 'periode' => Periode, 'tahun' => int]
    |            atau ['tipe' => 'total', 'tahun' => int]
    */

    private function kolomTemplate(Putaran $putaran): array
    {
        return RekonsiliasiPeriode::where('rekonsiliasi_id', $putaran->rekonsiliasi_id)
            ->with('periode')
            ->get()
            ->pluck('periode')
            ->sortBy(fn ($p) => [$p->tahun, $p->triwulan])
            ->map(fn ($p) => ['tipe' => 'periode', 'periode' => $p, 'tahun' => (int) $p->tahun])
            ->values()
            ->all();
    }

    private function kolomLengkap(int $modulId, Periode $batas): array
    {
        $periode = Periode::where('tahun', '>=', self::TAHUN_AWAL[$modulId])
            ->where('tahun', '<=', $batas->tahun)
            ->get()
            ->keyBy(fn ($p) => $p->tahun . '-' . $p->triwulan);

        $kolom = [];

        for ($tahun = self::TAHUN_AWAL[$modulId]; $tahun <= $batas->tahun; $tahun++) {

            for ($triwulan = 1; $triwulan <= 4; $triwulan++) {

                $p = $periode->get($tahun . '-' . $triwulan);

                // Triwulan setelah batas rekonsiliasi tetap ada sebagai kolom kosong.
                $diLuarBatas = $tahun == $batas->tahun && $triwulan > $batas->triwulan;

                $kolom[] = [
                    'tipe' => 'periode',
                    'periode' => $diLuarBatas ? null : $p,
                    'triwulan' => $triwulan,
                    'tahun' => $tahun,
                ];
            }

            $kolom[] = ['tipe' => 'total', 'tahun' => $tahun];
        }

        return $kolom;
    }


    /*
    |--------------------------------------------------------------------------
    | TABEL
    |--------------------------------------------------------------------------
    */

    private function tulisTabel(
        $sheet,
        int $offset,
        int $modulId,
        int $jenisTabelId,
        Wilayah $wilayah,
        Putaran $putaran,
        array $kolom,
        Collection $kategori,
        Collection $nilai,
        bool $lengkap
    ): void {

        $lu = $modulId === PdrbModul::LAPANGAN_USAHA;
        $kolomAkhir = Coordinate::stringFromColumnIndex(self::KOLOM_AWAL + count($kolom) - 1);
        $baris = fn (int $relatif) => $offset + $relatif;

        // Judul dan wilayah
        [$judul, $satuan] = self::JUDUL_TABEL[$jenisTabelId];
        $menurut = $lu ? 'LAPANGAN USAHA' : 'PENGELUARAN';

        $sheet->setCellValue('A' . $baris(1), "TABEL {$jenisTabelId}. {$judul} MENURUT {$menurut} ({$satuan})");
        $sheet->getStyle('A' . $baris(1))->getFont()->setBold(true);

        if ($lu) {
            $sheet->setCellValue('A' . $baris(2), 'Wilayah :');
            $sheet->setCellValue('C' . $baris(2), $wilayah->kodeBerkas() . ' ' . $wilayah->nama);
        } else {
            $sheet->setCellValue('A' . $baris(2), 'Kab/Kota');
            $sheet->setCellValue('B' . $baris(2), ':');
            $sheet->setCellValue('C' . $baris(2), $wilayah->nama);
        }

        $periodeRekon = $putaran->rekonsiliasi->periode;
        $sheet->setCellValue(
            'A' . $baris(3),
            "Rekonsiliasi {$periodeRekon->tahun} Q{$periodeRekon->triwulan} - Putaran {$putaran->nomor}"
                . ($lengkap ? '' : ' (ubah hanya nilai yang perlu diperbaiki)')
        );
        $sheet->getStyle('A' . $baris(3))->getFont()->setItalic(true)->setSize(9);

        // Header
        $sheet->setCellValue('A' . $baris(4), 'Kategori');
        $sheet->setCellValue('B' . $baris(4), 'Uraian');
        $sheet->mergeCells('A' . $baris(4) . ':A' . $baris(6));
        $sheet->mergeCells('B' . $baris(4) . ':C' . $baris(6));

        $this->tulisHeaderPeriode($sheet, $baris(4), $kolom, $lu);

        $sheet->getStyle('D' . $baris(4) . ':' . $kolomAkhir . $baris(7))
            ->getNumberFormat()->setFormatCode('General');

        $sheet->getStyle('A' . $baris(4) . ':' . $kolomAkhir . $baris(6))->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Nomor kolom
        for ($i = 1; $i <= 3 + count($kolom); $i++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i) . $baris(7), "({$i})");
        }
        $sheet->getStyle('A' . $baris(7) . ':' . $kolomAkhir . $baris(7))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data per kategori (urut id = urutan baris yang dibaca importer)
        $sumberTabel = in_array($jenisTabelId, [1, 2], true);
        $nomor = 0;

        foreach ($kategori as $item) {

            $r = $baris(self::BARIS_DATA + $nomor);
            $nomor++;

            $this->tulisLabel($sheet, $r, $item);

            $this->tulisAngka(
                $sheet,
                $r,
                $this->nilaiBaris($kolom, $wilayah->id, $jenisTabelId, [$item->id], $nilai, $sumberTabel)
            );

            if ($item->level == 1) {
                $sheet->getStyle('A' . $r . ':' . $kolomAkhir . $r)->getFont()->setBold(true);
            }
        }

        $barisAkhirData = $baris(self::BARIS_DATA + $nomor - 1);

        // Baris PDRB (ADHB/ADHK, ekspor lengkap)
        if ($lengkap) {

            $induk = $kategori->whereNull('parent_id')->pluck('id')->all();

            $sheet->setCellValue('B' . $baris(self::BARIS_PDRB), $lu ? ' PRODUK DOMESTIK REGIONAL BRUTO' : 'PDRB');

            if ($lu) {
                $sheet->setCellValue('B' . $baris(self::BARIS_PDRB + 1), ' PRODUK DOMESTIK REGIONAL BRUTO TANPA MIGAS');
            }

            if ($sumberTabel) {

                $this->tulisAngka(
                    $sheet,
                    $baris(self::BARIS_PDRB),
                    $this->nilaiBaris($kolom, $wilayah->id, $jenisTabelId, $induk, $nilai, true)
                );

                if ($lu) {
                    $this->tulisAngka(
                        $sheet,
                        $baris(self::BARIS_PDRB + 1),
                        $this->nilaiBaris($kolom, $wilayah->id, $jenisTabelId, $induk, $nilai, true, self::KATEGORI_MIGAS)
                    );
                }
            }

            $sheet->getStyle('A' . $baris(self::BARIS_PDRB) . ':' . $kolomAkhir . $baris(self::BARIS_PDRB + 1))
                ->getFont()->setBold(true);

            $barisAkhirData = $baris(self::BARIS_PDRB + ($lu ? 1 : 0));
        }

        $sheet->getStyle('A' . $baris(self::BARIS_DATA) . ':' . $kolomAkhir . $barisAkhirData)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }


    /*
    | LU   : baris +4 tahun (digabung per tahun), baris +5 I/II/III/IV/Total
    | PG   : baris +4 "I-2025" / "Total-2025"
    */
    private function tulisHeaderPeriode($sheet, int $barisHeader, array $kolom, bool $lu): void
    {
        $perTahun = [];

        foreach ($kolom as $i => $k) {

            $huruf = Coordinate::stringFromColumnIndex(self::KOLOM_AWAL + $i);

            $label = $k['tipe'] === 'total'
                ? 'Total'
                : self::ROMAWI[$k['periode']->triwulan ?? $k['triwulan']];

            if ($lu) {
                $sheet->setCellValue($huruf . ($barisHeader + 1), $label);
                $perTahun[$k['tahun']][] = $huruf;
            } else {
                $sheet->setCellValue($huruf . $barisHeader, $label . '-' . $k['tahun']);
                $sheet->mergeCells($huruf . $barisHeader . ':' . $huruf . ($barisHeader + 2));
            }
        }

        foreach ($perTahun as $tahun => $hurufTahun) {

            $sheet->setCellValue($hurufTahun[0] . $barisHeader, $tahun);

            if (count($hurufTahun) > 1) {
                $sheet->mergeCells($hurufTahun[0] . $barisHeader . ':' . end($hurufTahun) . $barisHeader);
            }

            foreach ($hurufTahun as $huruf) {
                $sheet->mergeCells($huruf . ($barisHeader + 1) . ':' . $huruf . ($barisHeader + 2));
            }
        }
    }


    /*
    | Angka ditulis langsung sebagai numerik (tanpa deteksi tipe per sel);
    | sel kosong dilewati. Jauh lebih cepat dari fromArray untuk ekspor
    | lengkap (puluhan ribu sel).
    */
    private function tulisAngka($sheet, int $r, array $nilai): void
    {
        foreach ($nilai as $i => $angka) {

            if ($angka === null) {
                continue;
            }

            $sheet->setCellValueExplicit(
                [self::KOLOM_AWAL + $i, $r],
                $angka,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
            );
        }
    }


    /*
    | Level 1: kode di A, nama di B; level 2: kode di B, nama di C;
    | level 3: "a. Nama" di C (seperti file data dasar).
    */
    private function tulisLabel($sheet, int $r, $item): void
    {
        if ($item->level == 1) {
            $sheet->setCellValue('A' . $r, $item->kode);
            $sheet->setCellValue('B' . $r, $item->nama);
        } elseif ($item->level == 2) {
            $sheet->setCellValueExplicit('B' . $r, (string) $item->kode, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $r, $item->nama);
        } else {
            $sheet->setCellValue('C' . $r, $item->kode . '. ' . $item->nama);
        }
    }


    /*
    | Nilai satu baris. Untuk lebih dari satu kategori (baris PDRB) nilai
    | dijumlahkan, dikurangi $kurangi (PDRB tanpa migas). Kolom Total =
    | jumlah triwulan (kosong = 0) hanya untuk ADHB/ADHK.
    */
    private function nilaiBaris(
        array $kolom,
        int $wilayahId,
        int $jenisTabelId,
        array $kategoriIds,
        Collection $nilai,
        bool $bisaDijumlah,
        array $kurangi = []
    ): array {

        $hasil = [];
        $totalTahun = [];

        foreach ($kolom as $k) {

            if ($k['tipe'] === 'total') {
                $hasil[] = $bisaDijumlah ? ($totalTahun[$k['tahun']] ?? 0) : null;
                continue;
            }

            $angka = null;

            if ($k['periode']) {

                foreach ($kategoriIds as $kategoriId) {
                    $row = $nilai->get(EffectiveValueService::kunci($wilayahId, $k['periode']->id, $jenisTabelId, $kategoriId));
                    if ($row) {
                        $angka = ($angka ?? 0) + (float) $row->nilai;
                    }
                }

                if ($angka !== null) {
                    foreach ($kurangi as $kategoriId) {
                        $row = $nilai->get(EffectiveValueService::kunci($wilayahId, $k['periode']->id, $jenisTabelId, $kategoriId));
                        $angka -= $row ? (float) $row->nilai : 0;
                    }
                }
            }

            $totalTahun[$k['tahun']] = ($totalTahun[$k['tahun']] ?? 0) + ($angka ?? 0);

            $hasil[] = $angka;
        }

        return $hasil;
    }


    private function kategori(int $modulId): Collection
    {
        $model = $modulId === PdrbModul::LAPANGAN_USAHA
            ? KategoriLapanganUsaha::class
            : KategoriPengeluaran::class;

        // Urut id: importer memetakan baris ke-n sebagai kategori id n.
        return $model::orderBy('id')->get(['id', 'kode', 'nama', 'level', 'parent_id']);
    }
}

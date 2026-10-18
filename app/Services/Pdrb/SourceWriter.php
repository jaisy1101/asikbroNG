<?php

namespace App\Services\Pdrb;

use App\Models\Putaran;
use App\Models\RekonsiliasiPeriode;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| PENYIMPANAN SOURCE PER PUTARAN
|--------------------------------------------------------------------------
|
| Dipakai bersama oleh unggah Excel dan input form.
|
| Aturan:
| - Hanya putaran berstatus berlangsung yang boleh ditulis.
| - Hanya periode dalam cakupan rekonsiliasi putaran tersebut.
| - Nilai dibandingkan dengan nilai efektif sebelum putaran ini:
|     berbeda  -> disimpan sebagai revisi putaran ini (upsert, ditimpa)
|     sama     -> revisi putaran ini dihapus, nilai kembali diwarisi
| - Data dasar dan revisi putaran lain tidak pernah disentuh.
|
| Format $cells:
|   [
|     ['periode_id' => 10, 'kategori_id' => 3, 'nilai' => 123.45],
|     ['periode_id' => 10, 'kategori_id' => null, 'nilai' => 999],  // TOTAL LU
|   ]
|
*/

class SourceWriter
{
    public function __construct(
        private EffectiveValueService $effective
    ) {
    }


    /*
    | Mengembalikan:
    |   jumlah_perubahan : sel yang nilai efektifnya berubah karena aksi ini
    |   periode_berubah  : periode_id yang memiliki sel berubah
    */
    public function simpan(
        Submission $submission,
        int $jenisTabelId,
        array $cells
    ): array {

        $modulId = (int) $submission->modul_id;
        $wilayahId = (int) $submission->wilayah_id;
        $putaranId = (int) $submission->putaran_id;

        $model = PdrbModul::model($modulId);
        $kategoriColumn = PdrbModul::kategoriColumn($modulId);
        $kategoriKeyColumn = PdrbModul::kategoriKeyColumn($modulId);

        // Sel ganda: nilai terakhir yang berlaku.
        $input = [];

        foreach ($cells as $cell) {

            $kunci = EffectiveValueService::kunci(
                $wilayahId,
                $cell['periode_id'],
                $jenisTabelId,
                $cell['kategori_id']
            );

            $input[$kunci] = $cell;
        }

        if (empty($input)) {
            return [
                'jumlah_perubahan' => 0,
                'periode_berubah' => [],
            ];
        }

        $periodeIds = array_values(array_unique(
            array_map(fn ($cell) => (int) $cell['periode_id'], $input)
        ));

        return DB::transaction(function () use (
            $model,
            $modulId,
            $wilayahId,
            $putaranId,
            $submission,
            $jenisTabelId,
            $kategoriColumn,
            $kategoriKeyColumn,
            $input,
            $periodeIds
        ) {

            // Mengunci baris putaran agar putaran tidak ditutup
            // di tengah proses penyimpanan.
            $putaran = Putaran::lockForUpdate()->find($putaranId);

            $this->pastikanBolehDisimpan($putaran, $periodeIds);

            $sebelum = $this->effective->sourceSebelum(
                $modulId,
                $wilayahId,
                $periodeIds,
                $jenisTabelId,
                $putaranId
            );

            $saatIni = $this->effective->source(
                $modulId,
                $wilayahId,
                $periodeIds,
                $jenisTabelId,
                $putaranId
            );

            $now = now();
            $revisi = [];
            $kembalikan = [];
            $jumlahPerubahan = 0;
            $periodeBerubah = [];

            foreach ($input as $kunci => $cell) {

                $nilaiBaru = $this->normalisasi($cell['nilai']);

                $nilaiSebelum = $sebelum->has($kunci)
                    ? $this->normalisasi($sebelum[$kunci]->nilai)
                    : null;

                $nilaiSaatIni = $saatIni->has($kunci)
                    ? $this->normalisasi($saatIni[$kunci]->nilai)
                    : null;

                if (!$this->sama($nilaiBaru, $nilaiSaatIni)) {
                    $jumlahPerubahan++;
                    $periodeBerubah[(int) $cell['periode_id']] = true;
                }

                if ($this->sama($nilaiBaru, $nilaiSebelum)) {

                    $kembalikan[(int) $cell['periode_id']][] =
                        PdrbModul::kategoriKey($cell['kategori_id']);

                    continue;
                }

                $revisi[] = [
                    'submission_id' => $submission->id,
                    'putaran_id' => $putaranId,
                    'wilayah_id' => $wilayahId,
                    'periode_id' => (int) $cell['periode_id'],
                    'jenis_tabel_id' => $jenisTabelId,
                    $kategoriColumn => $cell['kategori_id'],
                    'nilai' => $nilaiBaru,
                    'tipe_data' => 'source',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Nilai kembali sama dengan sebelum putaran ini:
            // hapus revisi putaran ini saja.
            foreach ($kembalikan as $periodeId => $kategoriKeys) {

                $model::where('wilayah_id', $wilayahId)
                    ->where('periode_id', $periodeId)
                    ->where('jenis_tabel_id', $jenisTabelId)
                    ->where('tipe_data', 'source')
                    ->where('putaran_id', $putaranId)
                    ->whereIn($kategoriKeyColumn, $kategoriKeys)
                    ->delete();
            }

            foreach (array_chunk($revisi, 500) as $chunk) {

                $model::upsert(
                    $chunk,
                    PdrbModul::uniqueColumns($modulId),
                    ['nilai', 'submission_id', 'updated_at']
                );
            }

            return [
                'jumlah_perubahan' => $jumlahPerubahan,
                'periode_berubah' => array_keys($periodeBerubah),
            ];
        });
    }


    /*
    | Menggabungkan hasil simpan beberapa tabel (misalnya ADHB dan ADHK).
    | Hasil NULL (tidak ada yang disimpan) diabaikan.
    */
    public static function gabungHasil(?array ...$hasil): array
    {
        $jumlah = 0;
        $periode = [];

        foreach ($hasil as $item) {

            if ($item === null) {
                continue;
            }

            $jumlah += $item['jumlah_perubahan'];

            foreach ($item['periode_berubah'] as $periodeId) {
                $periode[$periodeId] = true;
            }
        }

        return [
            'jumlah_perubahan' => $jumlah,
            'periode_berubah' => array_keys($periode),
        ];
    }


    /*
    | Periode yang boleh diunggah/diedit pada putaran ini.
    */
    public function periodeTerbuka(Putaran $putaran): array
    {
        return RekonsiliasiPeriode::where(
            'rekonsiliasi_id',
            $putaran->rekonsiliasi_id
        )
            ->pluck('periode_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }


    private function pastikanBolehDisimpan(
        ?Putaran $putaran,
        array $periodeIds
    ): void {

        if (!$putaran) {
            throw new PenyimpananDitolakException(
                'Putaran tidak ditemukan.'
            );
        }

        if ($putaran->status !== 'berlangsung') {
            throw new PenyimpananDitolakException(
                'Putaran sudah ditutup. Nilai final putaran tidak dapat diubah.'
            );
        }

        if (
            !$putaran->rekonsiliasi
            || $putaran->rekonsiliasi->status !== 'berlangsung'
        ) {
            throw new PenyimpananDitolakException(
                'Rekonsiliasi untuk putaran ini tidak sedang berlangsung.'
            );
        }

        $diluarCakupan = array_diff(
            $periodeIds,
            $this->periodeTerbuka($putaran)
        );

        if (!empty($diluarCakupan)) {
            throw new PenyimpananDitolakException(
                'Terdapat periode di luar cakupan rekonsiliasi yang berlangsung.'
            );
        }
    }


    /*
    | Dua nilai dianggap sama bila selisihnya tidak lebih dari satu
    | satuan presisi DECIMAL(30,6). MySQL membulatkan desimal ke atas
    | (514226.4455875 -> .445588), sedangkan float PHP bisa membulatkan
    | ke .445587; tanpa toleransi, nilai yang sama terbaca sebagai revisi.
    | NULL = sel belum memiliki nilai.
    */
    private function sama(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return abs((float) $a - (float) $b) <= 0.0000011;
    }


    /*
    | Disamakan dengan presisi DECIMAL(30,6) pada database.
    | Nilai kosong/bukan angka menjadi 0 (aturan template Excel).
    */
    private function normalisasi($nilai): string
    {
        if ($nilai === null || $nilai === '' || !is_numeric($nilai)) {
            $nilai = 0;
        }

        $nilai = round((float) $nilai, 6);

        // Hindari "-0.000000" yang berbeda dari "0.000000".
        if ($nilai == 0) {
            $nilai = 0.0;
        }

        return number_format($nilai, 6, '.', '');
    }
}

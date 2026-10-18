<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\PdrbTableController;
use App\Models\Submission;
use App\Services\Derived\DerivedLapanganUsaha;
use App\Services\Pdrb\EffectiveValueService;
use App\Services\Pdrb\PenyimpananDitolakException;
use App\Services\Pdrb\PeriodeTerdampak;
use App\Services\Pdrb\SourceWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SkemaPdrbSqlite;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ATURAN NILAI EFEKTIF DAN HISTORI PUTARAN
|--------------------------------------------------------------------------
|
| Nilai efektif pada putaran X = data dasar, ditimpa revisi semua putaran
| dengan putaran.id <= X (lintas rekonsiliasi). Hanya perubahan yang
| disimpan; histori putaran yang ditutup tidak boleh berubah.
|
*/

class NilaiEfektifPdrbTest extends TestCase
{
    use SkemaPdrbSqlite;

    private const WILAYAH = 2;
    private const KATEGORI = 1;
    private const ADHB = 1;

    // Periode: 1 = 2025 Q1, 2 = 2025 Q2, 3 = 2025 Q3, 4 = 2025 Q4,
    //          5 = 2026 Q1, 6 = 2026 Q2
    private const P_2025Q1 = 1;
    private const P_2025Q2 = 2;
    private const P_2026Q1 = 5;
    private const P_2026Q2 = 6;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buatSkemaPdrb();
    }


    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    private function dataDasar(float $nilai, int $periodeId = self::P_2026Q1, ?int $kategoriId = self::KATEGORI, int $jenis = self::ADHB, string $tabel = 'data_pdrb_lapangan_usaha'): void
    {
        $kolom = $tabel === 'data_pdrb_lapangan_usaha' ? 'kategori_lapus_id' : 'kategori_pengeluaran_id';

        DB::table($tabel)->insert([
            'wilayah_id' => self::WILAYAH,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => $jenis,
            $kolom => $kategoriId,
            'nilai' => $nilai,
            'tipe_data' => 'source',
        ]);
    }

    /*
    | Membuka rekonsiliasi baru (menutup yang berlangsung) dan putaran 0.
    | Mengembalikan id putaran.
    */
    private function bukaRekonsiliasi(array $periodeIds): int
    {
        $this->tutupSemua();

        $rekonsiliasiId = DB::table('rekonsiliasi')->insertGetId([
            'periode_id' => end($periodeIds),
            'status' => 'berlangsung',
        ]);

        foreach ($periodeIds as $periodeId) {
            DB::table('rekonsiliasi_periode')->insert([
                'rekonsiliasi_id' => $rekonsiliasiId,
                'periode_id' => $periodeId,
            ]);
        }

        return DB::table('putaran')->insertGetId([
            'rekonsiliasi_id' => $rekonsiliasiId,
            'nomor' => 0,
            'status' => 'berlangsung',
        ]);
    }

    private function bukaPutaranBerikutnya(int $putaranId): int
    {
        $putaran = DB::table('putaran')->find($putaranId);

        DB::table('putaran')->where('id', $putaranId)->update(['status' => 'selesai']);

        return DB::table('putaran')->insertGetId([
            'rekonsiliasi_id' => $putaran->rekonsiliasi_id,
            'nomor' => $putaran->nomor + 1,
            'status' => 'berlangsung',
        ]);
    }

    private function tutupSemua(): void
    {
        DB::table('putaran')->update(['status' => 'selesai']);
        DB::table('rekonsiliasi')->update(['status' => 'selesai']);
    }

    private function submission(int $putaranId, int $modulId = 1): Submission
    {
        return Submission::create([
            'putaran_id' => $putaranId,
            'wilayah_id' => self::WILAYAH,
            'modul_id' => $modulId,
        ]);
    }

    private function simpan(int $putaranId, float $nilai, int $periodeId = self::P_2026Q1, ?int $kategoriId = self::KATEGORI, int $modulId = 1): array
    {
        return app(SourceWriter::class)->simpan(
            $this->submission($putaranId, $modulId),
            self::ADHB,
            [['periode_id' => $periodeId, 'kategori_id' => $kategoriId, 'nilai' => $nilai]]
        );
    }

    private function efektif(?int $putaranId = null, int $periodeId = self::P_2026Q1, ?int $kategoriId = self::KATEGORI, int $modulId = 1): ?float
    {
        $row = app(EffectiveValueService::class)
            ->source($modulId, self::WILAYAH, [$periodeId], [self::ADHB], $putaranId)
            ->get(EffectiveValueService::kunci(self::WILAYAH, $periodeId, self::ADHB, $kategoriId));

        return $row ? (float) $row->nilai : null;
    }

    private function barisRevisi(int $putaranId, string $tabel = 'data_pdrb_lapangan_usaha'): int
    {
        return DB::table($tabel)->where('putaran_id', $putaranId)->count();
    }


    /*
    |--------------------------------------------------------------------------
    | PEWARISAN DAN HISTORI
    |--------------------------------------------------------------------------
    */

    public function test_contoh_konsep_300_310_tetap_350_lintas_rekonsiliasi(): void
    {
        $this->dataDasar(300);

        $rekonA = $this->bukaRekonsiliasi([self::P_2026Q1]);
        $this->simpan($rekonA, 310);

        $rekonA1 = $this->bukaPutaranBerikutnya($rekonA);
        $tetap = $this->simpan($rekonA1, 310);

        $rekonB = $this->bukaRekonsiliasi([self::P_2026Q1, self::P_2026Q2]);
        $this->assertSame(310.0, $this->efektif($rekonB), 'Rekonsiliasi baru mewarisi nilai final rekonsiliasi sebelumnya.');
        $this->simpan($rekonB, 350);
        $this->tutupSemua();

        $this->assertSame(0, $tetap['jumlah_perubahan']);
        $this->assertSame(0, $this->barisRevisi($rekonA1), 'Putaran tanpa perubahan tidak menyimpan baris.');

        $this->assertSame(310.0, $this->efektif($rekonA));
        $this->assertSame(310.0, $this->efektif($rekonA1));
        $this->assertSame(350.0, $this->efektif($rekonB));
        $this->assertSame(350.0, $this->efektif());
        $this->assertSame(300.0, (float) DB::table('data_pdrb_lapangan_usaha')->whereNull('putaran_id')->value('nilai'), 'Data dasar tidak pernah diubah.');
    }

    public function test_urutan_mengikuti_putaran_id_saat_admin_membuka_periode_mundur(): void
    {
        $this->dataDasar(300, self::P_2025Q1);

        $rekon2026 = $this->bukaRekonsiliasi([self::P_2025Q1, self::P_2026Q1]);
        $this->simpan($rekon2026, 320, self::P_2025Q1);

        // Admin membuka rekonsiliasi yang lebih lama setelahnya.
        $rekon2025 = $this->bukaRekonsiliasi([self::P_2025Q1]);
        $this->assertSame(320.0, $this->efektif($rekon2025, self::P_2025Q1));
        $this->simpan($rekon2025, 330, self::P_2025Q1);

        $this->assertSame(330.0, $this->efektif(null, self::P_2025Q1));
        $this->assertSame(320.0, $this->efektif($rekon2026, self::P_2025Q1));
    }

    public function test_periode_tanpa_data_dasar_dapat_diisi(): void
    {
        $putaran = $this->bukaRekonsiliasi([self::P_2026Q2]);

        $this->assertNull($this->efektif($putaran, self::P_2026Q2));

        $hasil = $this->simpan($putaran, 125, self::P_2026Q2);

        $this->assertSame(1, $hasil['jumlah_perubahan']);
        $this->assertSame(125.0, $this->efektif(null, self::P_2026Q2));
    }


    /*
    |--------------------------------------------------------------------------
    | PENYIMPANAN DALAM SATU PUTARAN
    |--------------------------------------------------------------------------
    */

    public function test_ubah_berulang_dalam_satu_putaran_menimpa_satu_baris(): void
    {
        $this->dataDasar(310);
        $putaran = $this->bukaRekonsiliasi([self::P_2026Q1]);

        foreach ([320, 330, 325] as $nilai) {
            $this->simpan($putaran, $nilai);
        }

        $this->assertSame(1, $this->barisRevisi($putaran));
        $this->assertSame(325.0, $this->efektif());
    }

    public function test_kembali_ke_nilai_sebelumnya_menghapus_revisi_putaran_ini(): void
    {
        $this->dataDasar(310);
        $putaran = $this->bukaRekonsiliasi([self::P_2026Q1]);

        $this->simpan($putaran, 320);
        $hasil = $this->simpan($putaran, 310);

        $this->assertSame(1, $hasil['jumlah_perubahan'], 'Nilai efektif berubah dari 320 kembali ke 310.');
        $this->assertSame(0, $this->barisRevisi($putaran));
        $this->assertSame(310.0, $this->efektif());
    }

    public function test_total_null_dapat_direvisi_dan_dikembalikan(): void
    {
        $this->dataDasar(1000, self::P_2026Q1, null);
        $putaran = $this->bukaRekonsiliasi([self::P_2026Q1]);

        $this->simpan($putaran, 1200, self::P_2026Q1, null);
        $this->assertSame(1200.0, $this->efektif(null, self::P_2026Q1, null));

        $this->simpan($putaran, 1000, self::P_2026Q1, null);
        $this->assertSame(0, $this->barisRevisi($putaran), 'Revisi TOTAL (kategori NULL) ikut terhapus.');
    }

    public function test_selisih_pembulatan_desimal_tidak_dianggap_perubahan(): void
    {
        // MySQL membulatkan 514226.4455875 menjadi 514226.445588.
        $this->dataDasar(514226.445588);
        $putaran = $this->bukaRekonsiliasi([self::P_2026Q1]);

        $hasil = $this->simpan($putaran, 514226.4455875);

        $this->assertSame(0, $hasil['jumlah_perubahan']);
        $this->assertSame(0, $this->barisRevisi($putaran));
    }

    public function test_pengeluaran_mengikuti_aturan_yang_sama(): void
    {
        $this->dataDasar(500, self::P_2026Q1, self::KATEGORI, self::ADHB, 'data_pdrb_pengeluaran');
        $putaran = $this->bukaRekonsiliasi([self::P_2026Q1]);

        $this->simpan($putaran, 550, self::P_2026Q1, self::KATEGORI, 2);
        $this->assertSame(550.0, $this->efektif(null, self::P_2026Q1, self::KATEGORI, 2));

        $this->simpan($putaran, 500, self::P_2026Q1, self::KATEGORI, 2);
        $this->assertSame(0, $this->barisRevisi($putaran, 'data_pdrb_pengeluaran'));
    }


    /*
    |--------------------------------------------------------------------------
    | PENOLAKAN
    |--------------------------------------------------------------------------
    */

    public function test_menolak_penyimpanan_pada_putaran_yang_ditutup(): void
    {
        $this->dataDasar(300);
        $putaran = $this->bukaRekonsiliasi([self::P_2026Q1]);
        $this->simpan($putaran, 310);
        $this->tutupSemua();

        try {
            $this->simpan($putaran, 999);
            $this->fail('Penyimpanan pada putaran yang ditutup harus ditolak.');
        } catch (PenyimpananDitolakException) {
            $this->assertSame(310.0, $this->efektif($putaran), 'Nilai final putaran tidak berubah.');
        }
    }

    public function test_menolak_periode_di_luar_cakupan_rekonsiliasi(): void
    {
        $this->dataDasar(300, self::P_2025Q1);
        $putaran = $this->bukaRekonsiliasi([self::P_2026Q1]);

        $this->expectException(PenyimpananDitolakException::class);

        $this->simpan($putaran, 999, self::P_2025Q1);
    }


    /*
    |--------------------------------------------------------------------------
    | DERIVED
    |--------------------------------------------------------------------------
    */

    public function test_periode_terdampak_mengikuti_ketergantungan_derived(): void
    {
        DB::table('periode')->insert([
            ['id' => 7, 'tahun' => 2026, 'triwulan' => 3],
            ['id' => 8, 'tahun' => 2026, 'triwulan' => 4],
            ['id' => 9, 'tahun' => 2027, 'triwulan' => 1],
        ]);

        $label = fn (array $ids) => DB::table('periode')->whereIn('id', $ids)
            ->orderBy('tahun')->orderBy('triwulan')->get()
            ->map(fn ($p) => "{$p->tahun}Q{$p->triwulan}")->implode(',');

        $terdampak = app(PeriodeTerdampak::class);

        // CtC tahun berjalan dan tahun berikutnya, YtY tahun berikutnya.
        $this->assertSame('2025Q2,2025Q3,2025Q4,2026Q2,2026Q3,2026Q4', $label($terdampak->untuk([self::P_2025Q2])));

        // Q4: QtQ melewati pergantian tahun ke Q1 berikutnya.
        $this->assertSame('2025Q4,2026Q1,2026Q4', $label($terdampak->untuk([4])));
    }

    public function test_derived_putaran_memakai_nilai_efektif_dan_derived_dasar_tetap(): void
    {
        $this->dataDasar(60, self::P_2026Q1, 1);
        $this->dataDasar(40, self::P_2026Q1, 2);

        $derived = new DerivedLapanganUsaha;
        $derived->hitungDistribusi(self::WILAYAH, self::P_2026Q1);

        $putaran = $this->bukaRekonsiliasi([self::P_2026Q1]);
        $this->simpan($putaran, 160, self::P_2026Q1, 1);
        $derived->hitungDistribusi(self::WILAYAH, self::P_2026Q1, $putaran);

        // Ulang derived data dasar: tidak boleh terpengaruh revisi.
        $derived->hitungDistribusi(self::WILAYAH, self::P_2026Q1);

        $distribusi = fn (?int $putaranId) => (float) app(EffectiveValueService::class)
            ->derived(1, self::WILAYAH, [self::P_2026Q1], [3], $putaranId)
            ->get(EffectiveValueService::kunci(self::WILAYAH, self::P_2026Q1, 3, 1))
            ->nilai;

        $this->assertEqualsWithDelta(60, $distribusi(0), 0.000001, 'Derived data dasar.');
        $this->assertEqualsWithDelta(80, $distribusi($putaran), 0.000001, '160 / (160 + 40).');
        $this->assertEqualsWithDelta(80, $distribusi(null), 0.000001, 'Terbaru.');
    }


    /*
    |--------------------------------------------------------------------------
    | API TABEL
    |--------------------------------------------------------------------------
    */

    public function test_api_tabel_terbaru_histori_dan_periode_terbuka(): void
    {
        $this->dataDasar(300);
        $putaran = $this->bukaRekonsiliasi([self::P_2026Q1]);
        $this->simpan($putaran, 310);

        $baca = function (array $query = []) {
            $respons = app(PdrbTableController::class)
                ->showLapanganUsaha(Request::create('/x', 'GET', $query), self::WILAYAH, self::ADHB)
                ->getData(true);

            // Baris kategori A (baris pertama adalah TOTAL).
            $respons['table'][0] = collect($respons['table'])->firstWhere('kode', 'A');

            return $respons;
        };

        $terbaru = $baca();
        $this->assertEquals(310, $terbaru['table'][0]['2026 Q1']);
        $this->assertSame(['2026 Q1'], $terbaru['periode_terbuka']);
        $this->assertArrayNotHasKey('2026 Q2', $terbaru['table'][0], 'Periode setelah triwulan rekonsiliasi tidak ditampilkan.');

        $putaranBerikut = $this->bukaPutaranBerikutnya($putaran);
        $this->simpan($putaranBerikut, 350);
        $this->tutupSemua();

        $this->assertEquals(310, $baca(['putaran_id' => $putaran])['table'][0]['2026 Q1']);
        $this->assertEquals(350, $baca(['putaran_id' => $putaranBerikut])['table'][0]['2026 Q1']);
        $this->assertSame([], $baca()['periode_terbuka'], 'Tidak ada putaran berlangsung.');

        $this->assertSame(404, app(PdrbTableController::class)
            ->showLapanganUsaha(Request::create('/x', 'GET', ['putaran_id' => 999]), self::WILAYAH, self::ADHB)
            ->getStatusCode());
    }

    public function test_api_tabel_baris_total_hanya_untuk_adhb_adhk(): void
    {
        // Kategori A dan B sama-sama induk (level 1).
        $this->dataDasar(300, self::P_2026Q1, 1);
        $this->dataDasar(200, self::P_2026Q1, 2);
        $this->dataDasar(50, self::P_2025Q1, 1);

        DB::table('data_pdrb_lapangan_usaha')->insert([
            'wilayah_id' => self::WILAYAH, 'periode_id' => self::P_2026Q1, 'jenis_tabel_id' => 3,
            'kategori_lapus_id' => 1, 'nilai' => 60, 'tipe_data' => 'derived',
        ]);

        $baca = fn (int $jenis) => app(PdrbTableController::class)
            ->showLapanganUsaha(Request::create('/x', 'GET'), self::WILAYAH, $jenis)
            ->getData(true)['table'];

        $adhb = $baca(self::ADHB);

        $this->assertSame('TOTAL', $adhb[0]['kategori']);
        $this->assertSame(0, $adhb[0]['level']);
        $this->assertEquals(500, $adhb[0]['2026 Q1'], 'A + B.');
        $this->assertEquals(50, $adhb[0]['2025 Q1'], 'B kosong dianggap 0.');
        $this->assertArrayNotHasKey('2025 Q2', $adhb[0], 'Periode tanpa data tetap kosong.');
        $this->assertCount(3, $adhb);

        $distribusi = $baca(3);

        $this->assertNotContains('TOTAL', array_column($distribusi, 'kategori'), 'Derived tidak dijumlahkan.');
    }
}

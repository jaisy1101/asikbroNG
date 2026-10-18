<?php

namespace Tests\Feature;

use App\Jobs\GenerateDerivedLapanganUsahaJob;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\SkemaPdrbSqlite;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ENTRI TABEL - ISI TABEL
|--------------------------------------------------------------------------
*/

class EntriTabelTest extends TestCase
{
    use SkemaPdrbSqlite;

    private const WILAYAH = 2;
    private const P_2026Q1 = 5;
    private const P_2026Q2 = 6;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buatSkemaPdrb();

        Bus::fake();

        $this->user = User::forceCreate([
            'username' => 'operator_a',
            'name' => 'Operator A',
            'role_id' => 2,
            'wilayah_id' => self::WILAYAH,
        ]);

        Sanctum::actingAs($this->user);
    }


    private function bukaPutaran(array $periodeIds = [self::P_2026Q1, self::P_2026Q2]): int
    {
        $rekonsiliasiId = DB::table('rekonsiliasi')->insertGetId([
            'periode_id' => end($periodeIds),
            'nama' => 'Rekonsiliasi PDRB 2026 Q2',
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

    private function dataDasar(float $nilai, int $periodeId, int $kategoriId, int $jenis = 1): void
    {
        DB::table('data_pdrb_lapangan_usaha')->insert([
            'wilayah_id' => self::WILAYAH,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => $jenis,
            'kategori_lapus_id' => $kategoriId,
            'nilai' => $nilai,
            'tipe_data' => 'source',
        ]);
    }

    /*
    | Isi tabel lengkap (2 periode x 2 kategori x ADHB/ADHK), seperti
    | yang dikirim halaman.
    */
    private function tabel(array $ubah = []): array
    {
        $nilai = [];

        foreach ([1, 2] as $jenis) {
            foreach ([self::P_2026Q1, self::P_2026Q2] as $periodeId) {
                foreach ([1, 2] as $kategoriId) {
                    $nilai[$jenis][] = [
                        'periode_id' => $periodeId,
                        'kategori_id' => $kategoriId,
                        'nilai' => $ubah["$jenis:$periodeId:$kategoriId"] ?? 100,
                    ];
                }
            }
        }

        return ['nilai' => $nilai];
    }

    private function isiDataDasarSemua(): void
    {
        foreach ([1, 2] as $jenis) {
            foreach ([self::P_2026Q1, self::P_2026Q2] as $periodeId) {
                foreach ([1, 2] as $kategoriId) {
                    $this->dataDasar(100, $periodeId, $kategoriId, $jenis);
                }
            }
        }
    }


    public function test_tanpa_putaran_berlangsung_ditolak(): void
    {
        $this->getJson('/api/entri-tabel/1')->assertStatus(409);
        $this->postJson('/api/entri-tabel/1', $this->tabel())->assertStatus(409);
    }

    public function test_menampilkan_periode_terbuka_kategori_dan_nilai_efektif(): void
    {
        $this->dataDasar(300, self::P_2026Q1, 1);
        $putaranId = $this->bukaPutaran([self::P_2026Q2, self::P_2026Q1]);

        $this->postJson('/api/entri-tabel/1', ['nilai' => [1 => [
            ['periode_id' => self::P_2026Q1, 'kategori_id' => 2, 'nilai' => 55],
        ]]])->assertOk();

        $respons = $this->getJson('/api/entri-tabel/1')->assertOk();

        $respons->assertJsonPath('putaran.id', $putaranId);
        $respons->assertJsonPath('periode.0.label', '2026 Q1');
        $respons->assertJsonPath('periode.1.label', '2026 Q2');
        $respons->assertJsonPath('kategori.0.kode', 'A');

        $nilai = $respons->json('nilai.1');

        $this->assertEquals(300, $nilai[self::P_2026Q1 . ':1']['nilai']);
        $this->assertFalse($nilai[self::P_2026Q1 . ':1']['diubah'], 'Nilai warisan tidak ditandai.');

        $this->assertEquals(55, $nilai[self::P_2026Q1 . ':2']['nilai']);
        $this->assertTrue($nilai[self::P_2026Q1 . ':2']['diubah']);
        $this->assertSame('Operator A', $nilai[self::P_2026Q1 . ':2']['oleh']);

        $this->assertArrayNotHasKey(self::P_2026Q2 . ':1', $nilai, 'Sel tanpa nilai tidak dikirim.');
    }

    public function test_simpan_membuat_submission_form_dan_menjalankan_derived(): void
    {
        $this->isiDataDasarSemua();
        $putaranId = $this->bukaPutaran();

        $this->postJson('/api/entri-tabel/1', $this->tabel([
            '1:' . self::P_2026Q1 . ':1' => 150,
            '2:' . self::P_2026Q2 . ':2' => 90,
        ]))
            ->assertOk()
            ->assertJsonPath('jumlah_perubahan', 2);

        $submission = DB::table('submission')->first();
        $this->assertSame('form', $submission->sumber);
        $this->assertEquals(2, $submission->jumlah_perubahan);
        $this->assertEquals(1, $submission->is_aktif);

        $this->assertSame(2, DB::table('data_pdrb_lapangan_usaha')->where('putaran_id', $putaranId)->count());

        // 2026 Q1 dan Q2 berubah -> derived untuk keduanya.
        Bus::assertDispatchedTimes(GenerateDerivedLapanganUsahaJob::class, 2);

        $this->assertSame(4, DB::table('integrasi_pdrb')->where('putaran_id', $putaranId)->count(), 'Integrasi dihitung ulang.');
    }

    public function test_simpan_tanpa_perubahan_tetap_tercatat_sebagai_konfirmasi(): void
    {
        $this->isiDataDasarSemua();
        $putaranId = $this->bukaPutaran();

        $this->postJson('/api/entri-tabel/1', $this->tabel())
            ->assertOk()
            ->assertJsonPath('jumlah_perubahan', 0);

        $this->assertEquals(0, DB::table('submission')->value('jumlah_perubahan'));
        $this->assertSame(0, DB::table('data_pdrb_lapangan_usaha')->where('putaran_id', $putaranId)->count());
        Bus::assertNotDispatched(GenerateDerivedLapanganUsahaJob::class);
    }

    public function test_simpan_ulang_menonaktifkan_submission_sebelumnya(): void
    {
        $this->isiDataDasarSemua();
        $this->bukaPutaran();

        $this->postJson('/api/entri-tabel/1', $this->tabel(['1:' . self::P_2026Q1 . ':1' => 150]))->assertOk();
        $this->postJson('/api/entri-tabel/1', $this->tabel(['1:' . self::P_2026Q1 . ':1' => 175]))->assertOk();

        $this->assertSame([0, 1], DB::table('submission')->orderBy('id')->pluck('is_aktif')->map(fn ($v) => (int) $v)->all());
        $this->assertSame([1, 2], DB::table('submission')->orderBy('id')->pluck('versi')->map(fn ($v) => (int) $v)->all());
        $this->assertEquals(175, DB::table('data_pdrb_lapangan_usaha')->whereNotNull('putaran_id')->value('nilai'));
    }

    public function test_sel_kosong_disimpan_sebagai_nol(): void
    {
        $putaranId = $this->bukaPutaran();

        $this->postJson('/api/entri-tabel/1', ['nilai' => [1 => [
            ['periode_id' => self::P_2026Q2, 'kategori_id' => 1, 'nilai' => null],
        ]]])->assertOk()->assertJsonPath('jumlah_perubahan', 1);

        $this->assertEquals(0, DB::table('data_pdrb_lapangan_usaha')->where('putaran_id', $putaranId)->value('nilai'));
    }

    public function test_menolak_periode_di_luar_cakupan(): void
    {
        $this->bukaPutaran([self::P_2026Q2]);

        $this->postJson('/api/entri-tabel/1', ['nilai' => [1 => [
            ['periode_id' => self::P_2026Q1, 'kategori_id' => 1, 'nilai' => 1],
        ]]])->assertStatus(422);

        $this->assertSame(0, DB::table('submission')->count(), 'Submission ikut dibatalkan.');
    }

    public function test_menolak_kategori_dan_jenis_tabel_tidak_dikenal(): void
    {
        $this->bukaPutaran();

        $this->postJson('/api/entri-tabel/1', ['nilai' => [1 => [
            ['periode_id' => self::P_2026Q1, 'kategori_id' => 999, 'nilai' => 1],
        ]]])->assertStatus(422);

        $this->postJson('/api/entri-tabel/1', ['nilai' => [3 => [
            ['periode_id' => self::P_2026Q1, 'kategori_id' => 1, 'nilai' => 1],
        ]]])->assertStatus(422);

        $this->postJson('/api/entri-tabel/1', ['nilai' => [1 => [
            ['periode_id' => self::P_2026Q1, 'kategori_id' => 1, 'nilai' => 'abc'],
        ]]])->assertStatus(422);

        $this->getJson('/api/entri-tabel/3')->assertNotFound();
    }

    public function test_aturan_induk_jumlah_dan_selisih_net_ekspor(): void
    {
        DB::table('kategori_pengeluaran')->insert([
            ['id' => 10, 'kode' => 'H', 'nama' => 'Net Ekspor', 'level' => 1, 'urutan' => 10, 'parent_id' => null],
            ['id' => 11, 'kode' => '1', 'nama' => 'Ekspor Antar Provinsi', 'level' => 2, 'urutan' => 11, 'parent_id' => 10],
            ['id' => 12, 'kode' => '2', 'nama' => 'Impor Antar Provinsi', 'level' => 2, 'urutan' => 12, 'parent_id' => 10],
            ['id' => 13, 'kode' => '1', 'nama' => 'Anak A', 'level' => 2, 'urutan' => 1, 'parent_id' => 1],
        ]);
        $this->bukaPutaran();

        $kategori = collect($this->getJson('/api/entri-tabel/2')->assertOk()->json('kategori'))->keyBy('id');

        $this->assertSame(['rumus' => 'jumlah', 'anak' => [13]], $kategori[1]['aturan_induk']);
        $this->assertSame(['rumus' => 'selisih', 'anak' => [11, 12]], $kategori[10]['aturan_induk']);
        $this->assertNull($kategori[2]['aturan_induk']);
    }

    public function test_halaman_entri_tabel_dikunci_tanpa_putaran_berlangsung(): void
    {
        $this->actingAs($this->user, 'web');

        $this->get('/lapangan-usaha/entri-tabel')
            ->assertOk()
            ->assertSee('Entri Tabel belum dapat dibuka')
            ->assertDontSee('id="btnSimpan"', false);

        $this->bukaPutaran();

        $this->get('/lapangan-usaha/entri-tabel')
            ->assertOk()
            ->assertSee('Unggah Berkas')
            ->assertSee('Isi Tabel')
            ->assertSee('const MODUL_ID = 1;', false);

        $this->get('/pengeluaran/entri-tabel')
            ->assertOk()
            ->assertSee('const MODUL_ID = 2;', false);
    }

    public function test_alamat_unggah_tabel_lama_dialihkan(): void
    {
        $this->actingAs($this->user, 'web');

        $this->get('/lapangan-usaha/unggah-tabel')->assertRedirect('/lapangan-usaha/entri-tabel');
        $this->get('/pengeluaran/unggah-tabel')->assertRedirect('/pengeluaran/entri-tabel');
    }

    public function test_status_menampilkan_putaran_konserda_terakhir_yang_ditutup(): void
    {
        DB::table('rekonsiliasi')->insert(['id' => 1, 'periode_id' => self::P_2026Q1, 'status' => 'berlangsung']);
        DB::table('putaran')->insert([
            ['id' => 1, 'rekonsiliasi_id' => 1, 'nomor' => 0, 'status' => 'selesai'],
            ['id' => 2, 'rekonsiliasi_id' => 1, 'nomor' => 1, 'status' => 'berlangsung'],
        ]);

        DB::table('putaran')->where('id', 1)->update([
            'tanggal_selesai' => '2026-10-18 15:33:30',
            'status_konserda' => 'selesai',
        ]);

        $this->getJson('/api/rekonsiliasi/status')
            ->assertOk()
            ->assertJsonPath('putaran_aktif.id', 2)
            ->assertJsonPath('putaran_konserda.id', 1)
            ->assertJsonPath('putaran_konserda.nomor', 0)
            ->assertJsonPath('putaran_konserda.rekonsiliasi.triwulan', 1)
            ->assertJsonPath('putaran_konserda.status_konserda', 'selesai')
            ->assertJsonPath('putaran_konserda.tanggal_selesai', '2026-10-18T15:33:30+00:00');
    }

    public function test_job_konserda_menandai_status_selesai_atau_gagal(): void
    {
        DB::table('rekonsiliasi')->insert(['id' => 1, 'periode_id' => self::P_2026Q1, 'status' => 'berlangsung']);
        DB::table('putaran')->insert(['id' => 1, 'rekonsiliasi_id' => 1, 'nomor' => 0, 'status' => 'selesai', 'status_konserda' => 'proses']);

        $berhasil = $this->mock(\App\Services\Konserda\KonserdaService::class);
        $berhasil->shouldReceive('generate')->once()->with(1);
        (new \App\Jobs\GenerateKonserdaJob(1))->handle($berhasil);

        $this->assertSame('selesai', DB::table('putaran')->value('status_konserda'));

        $gagal = $this->mock(\App\Services\Konserda\KonserdaService::class);
        $gagal->shouldReceive('generate')->once()->andThrow(new \RuntimeException('gagal hitung'));

        try {
            (new \App\Jobs\GenerateKonserdaJob(1))->handle($gagal);
            $this->fail('Exception harus diteruskan agar job tercatat gagal.');
        } catch (\RuntimeException) {
            $this->assertSame('gagal', DB::table('putaran')->value('status_konserda'));
        }
    }
}

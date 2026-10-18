<?php

namespace Tests\Feature;

use App\Models\Putaran;
use App\Models\Submission;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\LapanganUsaha\LapanganUsahaImportService;
use App\Services\Pdrb\PdrbExcelExport;
use App\Services\Pengeluaran\PengeluaranImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Feature\Concerns\SkemaPdrbSqlite;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| TEMPLATE, EKSPOR EXCEL, DAN NAMA BERKAS
|--------------------------------------------------------------------------
*/

class EksporExcelTest extends TestCase
{
    use SkemaPdrbSqlite;

    private const WILAYAH = 2;
    private const P_2025Q4 = 4;
    private const P_2026Q1 = 5;

    private const JUMLAH_KATEGORI = [1 => 65, 2 => 25];

    private int $putaranId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buatSkemaPdrb();

        Bus::fake();

        // Kategori lengkap seperti produksi (importer membaca baris 9.. sebagai id 1..n).
        foreach ([1 => 'kategori_lapangan_usaha', 2 => 'kategori_pengeluaran'] as $modul => $tabel) {
            DB::table($tabel)->where('id', 2)->update(['level' => 2, 'parent_id' => 1, 'kode' => '1']);
            for ($id = 3; $id <= self::JUMLAH_KATEGORI[$modul]; $id++) {
                DB::table($tabel)->insert(['id' => $id, 'kode' => 'K' . $id, 'nama' => "Kategori $id", 'level' => 1, 'urutan' => $id]);
            }
        }

        $rekonsiliasiId = DB::table('rekonsiliasi')->insertGetId([
            'periode_id' => self::P_2026Q1,
            'nama' => 'Rekonsiliasi PDRB 2026 Q1',
            'status' => 'berlangsung',
        ]);

        foreach ([self::P_2025Q4, self::P_2026Q1] as $periodeId) {
            DB::table('rekonsiliasi_periode')->insert(['rekonsiliasi_id' => $rekonsiliasiId, 'periode_id' => $periodeId]);
        }

        $this->putaranId = DB::table('putaran')->insertGetId([
            'rekonsiliasi_id' => $rekonsiliasiId,
            'nomor' => 0,
            'status' => 'berlangsung',
        ]);

        // Data dasar seluruh sel periode terbuka.
        foreach ([1 => ['data_pdrb_lapangan_usaha', 'kategori_lapus_id'], 2 => ['data_pdrb_pengeluaran', 'kategori_pengeluaran_id']] as $modul => [$tabel, $kolom]) {
            $rows = [];
            foreach ([1, 2] as $jenis) {
                foreach ([self::P_2025Q4, self::P_2026Q1] as $periodeId) {
                    for ($id = 1; $id <= self::JUMLAH_KATEGORI[$modul]; $id++) {
                        $rows[] = [
                            'wilayah_id' => self::WILAYAH, 'periode_id' => $periodeId, 'jenis_tabel_id' => $jenis,
                            $kolom => $id, 'nilai' => $id * 10 + $jenis + $periodeId / 10, 'tipe_data' => 'source',
                        ];
                    }
                }
            }
            DB::table($tabel)->insert($rows);
        }
    }


    private function excel(): PdrbExcelExport
    {
        return app(PdrbExcelExport::class);
    }

    private function wilayah(): Wilayah
    {
        return Wilayah::find(self::WILAYAH);
    }

    private function submission(int $modulId): Submission
    {
        Submission::where('modul_id', $modulId)->update(['is_aktif' => 0]);

        return Submission::create([
            'putaran_id' => $this->putaranId,
            'wilayah_id' => self::WILAYAH,
            'modul_id' => $modulId,
        ]);
    }

    private function bacaSheet(string $path)
    {
        return IOFactory::load($path)->getActiveSheet();
    }


    public function test_template_berisi_periode_terbuka_dan_kembali_tanpa_perubahan(): void
    {
        foreach ([1 => LapanganUsahaImportService::class, 2 => PengeluaranImportService::class] as $modul => $importer) {

            $putaran = Putaran::find($this->putaranId);
            $path = $this->excel()->simpanSementara($this->excel()->buat($modul, $this->wilayah(), $putaran, false));
            $sheet = $this->bacaSheet($path);

            if ($modul === 1) {
                $this->assertEquals(2025, $sheet->getCell('D4')->getValue());
                $this->assertSame('IV', $sheet->getCell('D5')->getValue());
                $this->assertEquals(2026, $sheet->getCell('E4')->getValue());
                $this->assertSame('I', $sheet->getCell('E5')->getValue());
                $this->assertEquals(2025, $sheet->getCell('D81')->getValue(), 'Header ADHK di baris 81.');
            } else {
                $this->assertSame('IV-2025', $sheet->getCell('D4')->getValue());
                $this->assertSame('I-2026', $sheet->getCell('E4')->getValue());
            }

            $this->assertNull($sheet->getCell('F4')->getValue(), 'Hanya periode terbuka, tanpa kolom Total.');
            $this->assertSame('A', $sheet->getCell('A9')->getValue());
            $this->assertEqualsWithDelta(11.4, $sheet->getCell('D9')->getValue(), 0.000001, 'Nilai efektif kategori 1, ADHB, 2025 Q4.');
            $this->assertEqualsWithDelta(12.5, $sheet->getCell('E86')->getValue(), 0.000001, 'ADHK di baris 86.');

            $hasil = app($importer)->import($path, $this->submission($modul));
            @unlink($path);

            $this->assertSame(0, $hasil['jumlah_perubahan'], "Modul $modul: template diunggah kembali tanpa diubah.");
        }
    }

    public function test_template_mengikuti_revisi_putaran_berlangsung(): void
    {
        DB::table('data_pdrb_lapangan_usaha')->insert([
            'putaran_id' => $this->putaranId, 'wilayah_id' => self::WILAYAH, 'periode_id' => self::P_2026Q1,
            'jenis_tabel_id' => 1, 'kategori_lapus_id' => 1, 'nilai' => 9999, 'tipe_data' => 'source',
        ]);

        $putaran = Putaran::find($this->putaranId);
        $path = $this->excel()->simpanSementara($this->excel()->buat(1, $this->wilayah(), $putaran, false));

        $this->assertEquals(9999, $this->bacaSheet($path)->getCell('E9')->getValue());
        @unlink($path);
    }

    public function test_ekspor_lengkap_sembilan_tabel_total_dan_pdrb(): void
    {
        $putaran = Putaran::find($this->putaranId);
        $path = $this->excel()->simpanSementara($this->excel()->buat(1, $this->wilayah(), $putaran, true));
        $sheet = $this->bacaSheet($path);

        foreach (range(1, 9) as $n) {
            $this->assertStringStartsWith("TABEL {$n}.", $sheet->getCell('A' . (77 * ($n - 1) + 1))->getValue());
        }

        // LU mulai 2010: 2010..2026 x 5 kolom; 2025 di kolom ke-16 tahun -> D + 15*5.
        $this->assertEquals(2010, $sheet->getCell('D4')->getValue());
        $this->assertSame('Total', $sheet->getCell('H5')->getValue());

        $kolom2025 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + 15 * 5);
        $kolomQ4 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + 15 * 5 + 3);
        $kolomTotal = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + 15 * 5 + 4);

        $this->assertEquals(2025, $sheet->getCell($kolom2025 . '4')->getValue());
        $this->assertEqualsWithDelta(11.4, $sheet->getCell($kolomQ4 . '9')->getValue(), 0.000001);
        $this->assertEqualsWithDelta(11.4, $sheet->getCell($kolomTotal . '9')->getValue(), 0.000001, 'Total = jumlah triwulan, kosong = 0.');

        // PDRB = jumlah kategori induk (semua kecuali kategori 2).
        $induk = collect(range(1, 65))->reject(fn ($id) => $id === 2)->sum(fn ($id) => $id * 10 + 1 + 0.4);
        $this->assertEqualsWithDelta($induk, $sheet->getCell($kolomQ4 . '74')->getValue(), 0.0001);
        $this->assertEqualsWithDelta($induk - (13 * 10 + 1.4) - (20 * 10 + 1.4), $sheet->getCell($kolomQ4 . '75')->getValue(), 0.0001, 'Tanpa migas.');

        // Derived: tidak ada total maupun PDRB.
        $this->assertNull($sheet->getCell($kolomTotal . (154 + 9))->getValue());
        $this->assertNull($sheet->getCell($kolomQ4 . (154 + 74))->getValue());

        @unlink($path);
    }

    public function test_nama_berkas_memuat_kode_wilayah(): void
    {
        $putaran = Putaran::find($this->putaranId);

        $this->assertSame('7301_Lapangan_Usaha_2026Q1_Putaran0.xlsx', $this->excel()->namaFile(1, $this->wilayah(), $putaran));
        $this->assertSame('7300_Pengeluaran_2026Q1_Putaran0.xlsx', $this->excel()->namaFile(2, Wilayah::find(1), $putaran));
    }


    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    public function test_unduh_template_untuk_wilayah_user(): void
    {
        Sanctum::actingAs(User::forceCreate(['username' => 'op', 'role_id' => 2, 'wilayah_id' => self::WILAYAH]));

        $this->get('/api/entri-tabel/1/template')
            ->assertOk()
            ->assertDownload('7301_Lapangan_Usaha_2026Q1_Putaran0.xlsx');

        DB::table('putaran')->update(['status' => 'selesai']);

        $this->getJson('/api/entri-tabel/1/template')->assertStatus(409);
    }

    public function test_ekspor_monitoring_hanya_admin_dan_minimal_satu_perubahan(): void
    {
        $url = "/api/monitoring/{$this->putaranId}/1/ekspor/" . self::WILAYAH;

        Sanctum::actingAs(User::forceCreate(['username' => 'op', 'role_id' => 2, 'wilayah_id' => self::WILAYAH]));
        $this->getJson($url)->assertForbidden();

        Sanctum::actingAs(User::forceCreate(['username' => 'admin', 'role_id' => 1, 'wilayah_id' => 1]));

        $this->getJson($url)->assertStatus(422);
        $this->assertFalse(collect($this->getJson("/api/monitoring/{$this->putaranId}/1")->json('data'))->firstWhere('wilayah_id', self::WILAYAH)['bisa_ekspor']);

        DB::table('data_pdrb_lapangan_usaha')->insert([
            'putaran_id' => $this->putaranId, 'wilayah_id' => self::WILAYAH, 'periode_id' => self::P_2026Q1,
            'jenis_tabel_id' => 1, 'kategori_lapus_id' => 1, 'nilai' => 9999, 'tipe_data' => 'source',
        ]);

        $this->assertTrue(collect($this->getJson("/api/monitoring/{$this->putaranId}/1")->json('data'))->firstWhere('wilayah_id', self::WILAYAH)['bisa_ekspor']);

        $this->get($url)->assertOk()->assertDownload('7301_Lapangan_Usaha_2026Q1_Putaran0.xlsx');
    }

    public function test_unggah_ditolak_bila_nama_berkas_tanpa_kode_wilayah(): void
    {
        config(['features.table_upload_enabled' => true]);

        Sanctum::actingAs(User::forceCreate(['username' => 'op', 'role_id' => 2, 'wilayah_id' => self::WILAYAH]));

        $this->postJson('/api/submission/upload', [
            'modul_id' => 1,
            'file' => UploadedFile::fake()->create('7302_Lapangan_Usaha.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Nama berkas harus mengandung kode wilayah Anda (7301).');

        $this->assertSame(0, DB::table('submission')->count());
    }
}

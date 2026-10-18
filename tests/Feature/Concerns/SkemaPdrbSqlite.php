<?php

namespace Tests\Feature\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| SKEMA PDRB DI SQLITE IN-MEMORY
|--------------------------------------------------------------------------
|
| Struktur tabel data PDRB disamakan dengan MySQL (kolom virtual versi_key
| dan kategori_key, UNIQUE INDEX per putaran) agar upsert SourceWriter
| berperilaku sama.
|
| Data awal: periode 1..6 = 2025 Q1..2026 Q2, provinsi (1, kode 73), Kab A (2, kode 7301),
| kategori A (1) dan B (2), jenis tabel 1-2 source dan 3 derived.
|
*/

trait SkemaPdrbSqlite
{
    protected function buatSkemaPdrb(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
            'logging.default' => 'null',
        ]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::create('periode', function (Blueprint $t) {
            $t->id();
            $t->integer('tahun');
            $t->integer('triwulan');
            $t->timestamps();
        });

        Schema::create('wilayah', function (Blueprint $t) {
            $t->id();
            $t->string('nama')->nullable();
            $t->string('jenis')->nullable();
            $t->string('kode_bps')->nullable();
            $t->integer('parent_id')->nullable();
            $t->timestamps();
        });

        foreach (['kategori_lapangan_usaha', 'kategori_pengeluaran'] as $tabel) {
            Schema::create($tabel, function (Blueprint $t) {
                $t->id();
                $t->string('kode')->nullable();
                $t->string('nama');
                $t->integer('parent_id')->nullable();
                $t->integer('level')->default(1);
                $t->integer('urutan')->default(0);
                $t->timestamps();
            });
        }

        Schema::create('jenis_tabel_pdrb', function (Blueprint $t) {
            $t->id();
            $t->string('tipe_data');
            $t->timestamps();
        });

        Schema::create('rekonsiliasi', function (Blueprint $t) {
            $t->id();
            $t->integer('periode_id');
            $t->string('nama')->nullable();
            $t->string('status');
            $t->timestamps();
        });

        Schema::create('rekonsiliasi_periode', function (Blueprint $t) {
            $t->id();
            $t->integer('rekonsiliasi_id');
            $t->integer('periode_id');
            $t->timestamps();
        });

        Schema::create('putaran', function (Blueprint $t) {
            $t->id();
            $t->integer('rekonsiliasi_id');
            $t->integer('nomor');
            $t->string('status');
            $t->string('status_konserda')->default('belum');
            $t->timestamp('tanggal_mulai')->nullable();
            $t->timestamp('tanggal_selesai')->nullable();
            $t->timestamps();
        });

        Schema::create('submission', function (Blueprint $t) {
            $t->id();
            $t->integer('putaran_id');
            $t->integer('user_id')->nullable();
            $t->integer('wilayah_id');
            $t->integer('modul_id');
            $t->string('sumber')->default('excel');
            $t->integer('versi')->default(1);
            $t->boolean('is_aktif')->default(true);
            $t->string('status')->nullable();
            $t->integer('jumlah_perubahan')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('username')->nullable();
            $t->string('name')->nullable();
            $t->integer('role_id')->default(2);
            $t->integer('wilayah_id')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->timestamps();
        });

        Schema::create('submission_files', function (Blueprint $t) {
            $t->id();
            $t->integer('submission_id');
            $t->string('nama_file')->nullable();
            $t->string('path_file')->nullable();
            $t->integer('ukuran_file')->nullable();
            $t->string('status_import')->nullable();
            $t->timestamp('uploaded_at')->nullable();
            $t->timestamps();
        });

        Schema::create('integrasi_pdrb', function (Blueprint $t) {
            $t->id();
            $t->integer('putaran_id');
            $t->integer('wilayah_id');
            $t->integer('periode_id');
            $t->integer('jenis_tabel_id');
            $t->double('total_lapus');
            $t->double('total_pengeluaran');
            $t->double('selisih');
            $t->string('status');
            $t->timestamps();
        });

        // Struktur sama dengan MySQL: kolom virtual + UNIQUE INDEX per putaran.
        foreach ([
            'data_pdrb_lapangan_usaha' => 'kategori_lapus_id',
            'data_pdrb_pengeluaran' => 'kategori_pengeluaran_id',
        ] as $tabel => $kategoriColumn) {

            Schema::create($tabel, function (Blueprint $t) use ($tabel, $kategoriColumn) {
                $t->id();
                $t->integer('submission_id')->nullable();
                $t->integer('putaran_id')->nullable();
                $t->integer('wilayah_id');
                $t->integer('periode_id');
                $t->integer('jenis_tabel_id');
                $t->integer($kategoriColumn)->nullable();
                $t->double('nilai');
                $t->string('tipe_data');
                $t->integer('versi_key')->virtualAs('coalesce(putaran_id, 0)');

                $kunci = $kategoriColumn;

                if ($tabel === 'data_pdrb_lapangan_usaha') {
                    $t->integer('kategori_key')->virtualAs('coalesce(kategori_lapus_id, 0)');
                    $kunci = 'kategori_key';
                }

                $t->unique([
                    'wilayah_id', 'periode_id', 'jenis_tabel_id',
                    $kunci, 'tipe_data', 'versi_key',
                ]);

                $t->timestamps();
            });
        }

        foreach ([[2025, 1], [2025, 2], [2025, 3], [2025, 4], [2026, 1], [2026, 2]] as $i => [$tahun, $triwulan]) {
            DB::table('periode')->insert(['id' => $i + 1, 'tahun' => $tahun, 'triwulan' => $triwulan]);
        }

        DB::table('wilayah')->insert(['id' => 1, 'nama' => 'Provinsi', 'jenis' => 'provinsi', 'kode_bps' => '73']);
        DB::table('wilayah')->insert(['id' => self::WILAYAH, 'nama' => 'Kab A', 'jenis' => 'kabupaten', 'kode_bps' => '7301', 'parent_id' => 1]);

        foreach (['kategori_lapangan_usaha', 'kategori_pengeluaran'] as $tabel) {
            DB::table($tabel)->insert(['id' => 1, 'kode' => 'A', 'nama' => 'Kategori A', 'level' => 1, 'urutan' => 1]);
            DB::table($tabel)->insert(['id' => 2, 'kode' => 'B', 'nama' => 'Kategori B', 'level' => 1, 'urutan' => 2]);
        }

        foreach (['source', 'source', 'derived'] as $i => $tipe) {
            DB::table('jenis_tabel_pdrb')->insert(['id' => $i + 1, 'tipe_data' => $tipe]);
        }
    }
}

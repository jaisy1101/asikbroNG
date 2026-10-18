<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Dua migrasi 2026_09_21 dengan nama sama tercatat sudah dijalankan,
     * tetapi kolom status_konserda tidak ada di database. Kolom dipulihkan
     * bila belum ada. Putaran lama yang sudah ditutup dan memiliki hasil
     * Konserda ditandai selesai.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('putaran', 'status_konserda')) {

            Schema::table('putaran', function (Blueprint $table) {
                $table->enum('status_konserda', [
                    'belum',
                    'proses',
                    'selesai',
                    'gagal'
                ])
                ->default('belum')
                ->after('status');
            });
        }

        DB::table('putaran')
            ->where('status', 'selesai')
            ->whereIn('id', DB::table('hasil_konserda')->select('putaran_id'))
            ->update(['status_konserda' => 'selesai']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('putaran', 'status_konserda')) {
            Schema::table('putaran', function (Blueprint $table) {
                $table->dropColumn('status_konserda');
            });
        }
    }
};

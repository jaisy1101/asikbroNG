<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Baris awal pengembangan mencatat tanggal_mulai dalam WIB (+7),
     * sementara tanggal_selesai sudah UTC, sehingga putaran/rekonsiliasi
     * tampak ditutup sebelum dibuka. Hanya baris dengan
     * tanggal_selesai < tanggal_mulai yang digeser -7 jam.
     */
    public function up(): void
    {
        foreach (['putaran', 'rekonsiliasi'] as $tabel) {

            DB::table($tabel)
                ->whereNotNull('tanggal_selesai')
                ->whereColumn('tanggal_selesai', '<', 'tanggal_mulai')
                ->update([
                    'tanggal_mulai' => DB::raw('DATE_SUB(tanggal_mulai, INTERVAL 7 HOUR)'),
                    'created_at' => DB::raw('DATE_SUB(created_at, INTERVAL 7 HOUR)'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Tidak dibalik: setelah diperbaiki, baris yang terdampak tidak lagi
     * dapat dibedakan dari baris lain.
     */
    public function down(): void
    {
        //
    }
};

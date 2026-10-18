<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Submission = satu aksi simpan (unggah Excel atau simpan form).
     * jumlah_perubahan = 0 berarti user mengonfirmasi nilai yang ada.
     */
    public function up(): void
    {
        Schema::table('submission', function (Blueprint $table) {

            $table->enum('sumber', ['excel', 'form'])
                ->default('excel')
                ->after('modul_id');

            // NULL untuk submission lama yang tidak tercatat jumlahnya.
            $table->unsignedInteger('jumlah_perubahan')
                ->nullable()
                ->after('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submission', function (Blueprint $table) {

            $table->dropColumn(['sumber', 'jumlah_perubahan']);

        });
    }
};

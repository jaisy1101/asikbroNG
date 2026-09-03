<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('rekonsiliasi_periode', function (Blueprint $table) {

            $table->id();

            $table->foreignId('rekonsiliasi_id')
                ->constrained('rekonsiliasi')
                ->cascadeOnDelete();

            $table->foreignId('periode_id')
                ->constrained('periode')
                ->cascadeOnDelete();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi_periode');
    }
};

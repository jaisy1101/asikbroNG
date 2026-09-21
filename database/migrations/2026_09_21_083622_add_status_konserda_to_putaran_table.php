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


    public function down()
    {
        Schema::table('putaran', function (Blueprint $table) {
            $table->dropColumn('status_konserda');
        });
    }
};

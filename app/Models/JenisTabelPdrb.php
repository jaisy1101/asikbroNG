<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisTabelPdrb extends Model
{
    protected $table = 'jenis_tabel_pdrb';

    protected $fillable = [
        'kode',
        'nama',
        'tipe_data',
        'urutan',
    ];
}
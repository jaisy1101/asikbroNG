<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    protected $table = 'wilayah';

    protected $fillable = [
        'nama'
    ];

    /*
    | Kode wilayah pada nama berkas: kabkota 4 digit (7301),
    | provinsi 2 digit ditambah 00 (73 -> 7300).
    */
    public function kodeBerkas(): string
    {
        $kode = (string) $this->kode_bps;

        return strlen($kode) === 2 ? $kode . '00' : $kode;
    }
}
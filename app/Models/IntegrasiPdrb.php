<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrasiPdrb extends Model
{
    protected $table = 'integrasi_pdrb';


    protected $fillable = [
        'putaran_id',

        'wilayah_id',

        'periode_id',

        'jenis_tabel_id',

        'total_lapus',

        'total_pengeluaran',

        'selisih',

        'status',

    ];



    public function putaran()
    {
        return $this->belongsTo(Putaran::class);
    }


    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }


    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }


    public function jenisTabel()
    {
        return $this->belongsTo(JenisTabelPdrb::class);
    }

}
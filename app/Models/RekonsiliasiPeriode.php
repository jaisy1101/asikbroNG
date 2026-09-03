<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekonsiliasiPeriode extends Model
{
    protected $table = 'rekonsiliasi_periode';

    protected $fillable = [
        'rekonsiliasi_id',
        'periode_id'
    ];


    public function rekonsiliasi()
    {
        return $this->belongsTo(Rekonsiliasi::class, 'rekonsiliasi_id');
    }


    public function periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }
}
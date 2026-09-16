<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilKonserdaDetail extends Model
{

    protected $fillable = [

        'hasil_konserda_id',
        'wilayah_id',
        'nilai',

    ];



    public function hasilKonserda()
    {
        return $this->belongsTo(
            HasilKonserda::class
        );
    }



    public function wilayah()
    {
        return $this->belongsTo(
            Wilayah::class
        );
    }

}
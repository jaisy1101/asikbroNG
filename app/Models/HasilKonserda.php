<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilKonserda extends Model
{

    protected $table = 'hasil_konserda';


    protected $fillable = [

        'putaran_id',
        'periode_id',
        'wilayah_parent_id',
        'modul_id',
        'jenis_tabel_id',
        'kategori_id',
        'nilai_provinsi',
        'nilai_agregasi_kabkota',
        'selisih',
        'diskrepansi_persen',
        'batas_toleransi',
        'status',

    ];



    public function putaran()
    {
        return $this->belongsTo(Putaran::class);
    }



    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }



    public function wilayahParent()
    {
        return $this->belongsTo(
            Wilayah::class,
            'wilayah_parent_id'
        );
    }



    public function detail()
    {
        return $this->hasMany(
            HasilKonserdaDetail::class
        );
    }


}
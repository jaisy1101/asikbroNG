<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPdrbLapanganUsaha extends Model
{
    protected $table = 'data_pdrb_lapangan_usaha';

    protected $fillable = [
        'submission_id',
        'wilayah_id',
        'periode_id',
        'jenis_tabel_id',
        'kategori_lapus_id',
        'nilai',
        'tipe_data',
    ];


    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }


    public function jenisTabel()
    {
        return $this->belongsTo(JenisTabelPdrb::class, 'jenis_tabel_id');
    }


    public function kategori()
    {
        return $this->belongsTo(KategoriLapanganUsaha::class, 'kategori_lapus_id');
    }


    public function periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }
}
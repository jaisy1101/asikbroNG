<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPdrbPengeluaran extends Model
{
    protected $table = 'data_pdrb_pengeluaran';

    protected $fillable = [
        'submission_id',
        'jenis_tabel_id',
        'kategori_id',
        'periode_id',
        'nilai'
    ];


    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }


    public function jenisTabel()
    {
        return $this->belongsTo(JenisTabelPdrb::class);
    }


    public function kategori()
    {
        return $this->belongsTo(KategoriPengeluaran::class);
    }


    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }
}

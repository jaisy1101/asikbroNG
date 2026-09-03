<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'submission';
    
    protected $fillable = [
    'putaran_id',
    'user_id',
    'wilayah_id',
    'modul_id',
    'versi',
    'is_aktif',
    'status',
    'submitted_at'
    ];

    public function dataPdrbLapanganUsaha()
    {
        return $this->hasMany(DataPdrbLapanganUsaha::class);
    }
    
    public function dataPdrbPengeluaran()
    {
        return $this->hasMany(DataPdrbPengeluaran::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function modul()
    {
        return $this->belongsTo(ModulPdrb::class, 'modul_id');
    }


    public function putaran()
    {
        return $this->belongsTo(Putaran::class, 'putaran_id');
    }


    public function files()
    {
        return $this->hasMany(SubmissionFile::class, 'submission_id');
    }
}

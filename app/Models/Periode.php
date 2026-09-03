<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    protected $table = 'periode';

    public function dataPdrbLapanganUsaha()
    {
        return $this->hasMany(DataPdrbLapanganUsaha::class);
    }
}
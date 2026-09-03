<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Putaran extends Model
{
    protected $table = 'putaran';

    protected $fillable = [
        'rekonsiliasi_id',
        'nomor',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    public function rekonsiliasi(): BelongsTo
    {
        return $this->belongsTo(Rekonsiliasi::class, 'rekonsiliasi_id');
    }
}

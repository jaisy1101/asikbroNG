<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rekonsiliasi extends Model
{
    protected $table = 'rekonsiliasi';

    protected $fillable = [
        'periode_id',
        'modul_id',
        'dibuat_oleh',
        'nama',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function modul(): BelongsTo
    {
        return $this->belongsTo(ModulPdrb::class, 'modul_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function putaran(): HasMany
    {
        return $this->hasMany(Putaran::class, 'rekonsiliasi_id');
    }
}
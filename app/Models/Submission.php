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
}

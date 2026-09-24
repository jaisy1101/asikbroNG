<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{

    protected $table = 'pengumuman';


    protected $fillable = [

        'user_id',

        'isi'

    ];



    public function user()
    {

        return $this->belongsTo(User::class);

    }

}
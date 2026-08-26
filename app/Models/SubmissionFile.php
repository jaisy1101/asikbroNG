<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionFile extends Model
{
    protected $table = 'submission_files';
    
    protected $fillable = [
    'submission_id',
    'nama_file',
    'path_file',
    'ukuran_file',
    'status_import',
    'pesan_error',
    'uploaded_at'
    ];
}

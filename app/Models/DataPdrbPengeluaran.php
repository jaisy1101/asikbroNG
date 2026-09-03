<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Submission;
use App\Models\JenisTabelPdrb;
use App\Models\KategoriPengeluaran;
use App\Models\Periode;

class DataPdrbPengeluaran extends Model
{
    protected $table = 'data_pdrb_pengeluaran';

    protected $fillable = [
    'submission_id',
    'wilayah_id',
    'periode_id',
    'jenis_tabel_id',
    'kategori_pengeluaran_id',
    'nilai',
    'tipe_data',
    ];  

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function jenisTabel(): BelongsTo
    {
        return $this->belongsTo(JenisTabelPdrb::class, 'jenis_tabel_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'kategori_pengeluaran_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }
}
<?php

namespace App\Jobs;

use App\Services\Derived\DerivedLapanganUsaha;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\support\Facades\Log;

class GenerateDerivedLapanganUsahaJob implements ShouldQueue
{
    use Queueable;


    public function __construct(
        public $wilayahId,
        public $periodeId
    ) {
    }


    public function handle(DerivedLapanganUsaha $service)
    {   
        Log::info("Mulai generate derived lapangan usaha", [
            'wilayah' => $this->wilayahId,
            'periode' => $this->periodeId
        ]);


        $service->hitungDistribusi(
            $this->wilayahId,
            $this->periodeId
        );


        $service->hitungQtQ(
            $this->wilayahId,
            $this->periodeId
        );


        $service->hitungYtY(
            $this->wilayahId,
            $this->periodeId
        );


        $service->hitungCtC(
            $this->wilayahId,
            $this->periodeId
        );


        $service->hitungImplisit(
            $this->wilayahId,
            $this->periodeId
        );


        $service->hitungImplisitQtQ(
            $this->wilayahId,
            $this->periodeId
        );


        $service->hitungImplisitYtY(
            $this->wilayahId,
            $this->periodeId
        );


        Log::info("Selesai generate derived lapangan usaha");
    }
}
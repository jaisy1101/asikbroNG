<?php

namespace App\Jobs;

use App\Services\Derived\DerivedPengeluaran;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateDerivedPengeluaranJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $wilayahId,
        public $periodeId,
        public $putaranId = null
    ) {
    }

    public function handle(DerivedPengeluaran $service)
    {
        Log::info("Mulai generate derived pengeluaran", [
            'wilayah' => $this->wilayahId,
            'periode' => $this->periodeId,
            'putaran' => $this->putaranId,
        ]);

        $service->hitungDistribusi(
            $this->wilayahId,
            $this->periodeId,
            $this->putaranId
        );

        $service->hitungQtQ(
            $this->wilayahId,
            $this->periodeId,
            $this->putaranId
        );

        $service->hitungYtY(
            $this->wilayahId,
            $this->periodeId,
            $this->putaranId
        );

        $service->hitungCtC(
            $this->wilayahId,
            $this->periodeId,
            $this->putaranId
        );

        $service->hitungImplisit(
            $this->wilayahId,
            $this->periodeId,
            $this->putaranId
        );

        $service->hitungImplisitQtQ(
            $this->wilayahId,
            $this->periodeId,
            $this->putaranId
        );

        $service->hitungImplisitYtY(
            $this->wilayahId,
            $this->periodeId,
            $this->putaranId
        );

        Log::info("Selesai generate derived pengeluaran", [
            'wilayah' => $this->wilayahId,
            'periode' => $this->periodeId,
            'putaran' => $this->putaranId,
        ]);
    }
}

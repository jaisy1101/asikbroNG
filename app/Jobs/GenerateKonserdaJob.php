<?php

namespace App\Jobs;

use App\Models\Putaran;
use App\Services\Konserda\KonserdaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class GenerateKonserdaJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, SerializesModels;


    public $putaranId;


    public function __construct($putaranId)
    {
        $this->putaranId = $putaranId;
    }



    public function handle(
        KonserdaService $konserda
    )
    {

        $this->tandai('proses');

        try {

            $konserda->generate(
                $this->putaranId
            );

        } catch (\Throwable $e) {

            $this->tandai('gagal');

            throw $e;

        }

        $this->tandai('selesai');

    }


    // Status perhitungan Konserda di putaran (ditampilkan di Hasil Konserda).
    private function tandai(string $status): void
    {
        Putaran::whereKey($this->putaranId)->update([
            'status_konserda' => $status,
        ]);
    }

}

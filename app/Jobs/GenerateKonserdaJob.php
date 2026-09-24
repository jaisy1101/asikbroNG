<?php

namespace App\Jobs;

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

        $konserda->generate(
            $this->putaranId
        );

    }

}

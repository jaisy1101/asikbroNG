<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Integrasi\IntegrasiPdrbService;

class GenerateIntegrasiPdrb extends Command
{

    protected $signature = 'integrasi:generate {rekonsiliasi_id}';


    protected $description = 'Generate integrasi PDRB';


    public function handle(
        IntegrasiPdrbService $service
    )
    {

        $service->generate(
            $this->argument('rekonsiliasi_id'),
            null
        );


        $this->info(
            'Integrasi berhasil dibuat'
        );

    }

}
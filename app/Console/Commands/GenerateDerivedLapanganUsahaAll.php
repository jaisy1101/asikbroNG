<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Jobs\GenerateDerivedLapanganUsahaJob;

#[Signature('app:generate-derived-lapangan-usaha-all')]
#[Description('Command description')]
class GenerateDerivedLapanganUsahaAll extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $wilayahIds = \App\Models\Wilayah::pluck('id');

        $periodeIds = \App\Models\Periode::pluck('id');


        foreach ($wilayahIds as $wilayahId) {

            foreach ($periodeIds as $periodeId) {

                GenerateDerivedLapanganUsahaJob::dispatch(
                    $wilayahId,
                    $periodeId
                );

            }
        }


        $this->info("Generate derived all dikirim ke queue.");
    }
}

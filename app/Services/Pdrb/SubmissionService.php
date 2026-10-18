<?php

namespace App\Services\Pdrb;

use App\Jobs\GenerateDerivedLapanganUsahaJob;
use App\Jobs\GenerateDerivedPengeluaranJob;
use App\Models\Putaran;
use App\Models\Rekonsiliasi;
use App\Models\Submission;
use App\Services\Integrasi\IntegrasiPdrbService;

/*
|--------------------------------------------------------------------------
| ALUR SUBMISSION BERSAMA
|--------------------------------------------------------------------------
|
| Dipakai oleh Unggah Berkas (Excel) dan Isi Tabel (form):
|   1. putaranBerlangsung()  -> putaran tujuan
|   2. buat()                -> submission baru (dalam transaksi pemanggil)
|   3. SourceWriter::simpan  -> oleh pemanggil
|   4. catatHasil()          -> jumlah_perubahan (dalam transaksi)
|   5. prosesLanjutan()      -> integrasi + derived (setelah commit)
|
*/

class SubmissionService
{
    public function __construct(
        private IntegrasiPdrbService $integrasi,
        private PeriodeTerdampak $periodeTerdampak
    ) {
    }


    public function putaranBerlangsung(): ?Putaran
    {
        $rekonsiliasi = Rekonsiliasi::where('status', 'berlangsung')
            ->latest('id')
            ->first();

        if (!$rekonsiliasi) {
            return null;
        }

        return Putaran::where('rekonsiliasi_id', $rekonsiliasi->id)
            ->where('status', 'berlangsung')
            ->latest('nomor')
            ->first();
    }


    /*
    | Submission lama wilayah/modul pada putaran ini dinonaktifkan;
    | submission baru menjadi satu-satunya yang aktif.
    */
    public function buat(
        $user,
        Putaran $putaran,
        int $modulId,
        string $sumber
    ): Submission {

        $wilayahId = $user->wilayah_id;

        $query = fn () => Submission::where('putaran_id', $putaran->id)
            ->where('wilayah_id', $wilayahId)
            ->where('modul_id', $modulId);

        $query()
            ->where('is_aktif', 1)
            ->update([
                'is_aktif' => 0
            ]);

        return Submission::create([

            'putaran_id' => $putaran->id,

            'user_id' => $user->id,

            'wilayah_id' => $wilayahId,

            'modul_id' => $modulId,

            'sumber' => $sumber,

            'versi' => ($query()->max('versi') ?? 0) + 1,

            'is_aktif' => 1,

            'status' => 'terupload',

            'submitted_at' => now(),

        ]);
    }


    public function catatHasil(Submission $submission, array $hasil): void
    {
        $submission->update([
            'jumlah_perubahan' => $hasil['jumlah_perubahan'],
        ]);
    }


    /*
    | Integrasi selalu dihitung ulang; derived hanya untuk periode yang
    | berubah dan periode yang bergantung padanya.
    */
    public function prosesLanjutan(Submission $submission, array $hasil): void
    {
        $this->integrasi->generate(
            $submission->putaran_id,
            $submission->wilayah_id
        );

        $job = (int) $submission->modul_id === PdrbModul::LAPANGAN_USAHA
            ? GenerateDerivedLapanganUsahaJob::class
            : GenerateDerivedPengeluaranJob::class;

        foreach ($this->periodeTerdampak->untuk($hasil['periode_berubah']) as $periodeId) {

            $job::dispatch(
                $submission->wilayah_id,
                $periodeId,
                $submission->putaran_id
            );
        }
    }
}

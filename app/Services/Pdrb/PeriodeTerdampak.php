<?php

namespace App\Services\Pdrb;

use App\Models\Periode;

/*
|--------------------------------------------------------------------------
| PERIODE YANG DERIVED-NYA PERLU DIHITUNG ULANG
|--------------------------------------------------------------------------
|
| Source periode P (tahun T, triwulan n) dipakai oleh:
|   Distribusi, Implisit         : P
|   QtQ, Implisit QtQ            : triwulan berikutnya
|   YtY, Implisit YtY            : Qn tahun T+1
|   CtC                          : Qn..Q4 tahun T dan Qn..Q4 tahun T+1
|
| Semua derived dihitung dari source efektif (bukan dari derived lain),
| sehingga cukup diperluas satu tingkat.
|
| Periode terdampak boleh berada di luar cakupan rekonsiliasi:
| derived bukan input user.
|
*/

class PeriodeTerdampak
{
    public function untuk(array $periodeIds): array
    {
        if (empty($periodeIds)) {
            return [];
        }

        $semua = Periode::orderBy('tahun')
            ->orderBy('triwulan')
            ->get(['id', 'tahun', 'triwulan']);

        $berubah = $semua->whereIn('id', $periodeIds);

        $hasil = [];

        foreach ($semua as $kandidat) {

            foreach ($berubah as $p) {

                if ($this->bergantung($kandidat, $p)) {
                    $hasil[] = (int) $kandidat->id;
                    break;
                }
            }
        }

        return $hasil;
    }


    private function bergantung($kandidat, $p): bool
    {
        // CtC tahun T dan T+1 (mencakup P sendiri dan QtQ dalam tahun yang sama)
        if (
            in_array($kandidat->tahun, [$p->tahun, $p->tahun + 1])
            && $kandidat->triwulan >= $p->triwulan
        ) {
            return true;
        }

        // QtQ melewati pergantian tahun: Q4 T -> Q1 T+1
        return $p->triwulan == 4
            && $kandidat->tahun == $p->tahun + 1
            && $kandidat->triwulan == 1;
    }
}

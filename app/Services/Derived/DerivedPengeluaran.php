<?php

namespace App\Services\Derived;

use App\Models\DataPdrbPengeluaran;
use App\Models\Periode;
use App\Models\Putaran;
use App\Services\Pdrb\EffectiveValueService;
use App\Services\Pdrb\PdrbModul;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DerivedPengeluaran
{
    /*
    |--------------------------------------------------------------------------
    | NILAI SOURCE EFEKTIF
    |--------------------------------------------------------------------------
    | Mengikuti EffectiveValueService: data dasar ditimpa revisi semua putaran
    | dengan putaran.id <= putaran target, lintas rekonsiliasi.
    | Tanpa putaran (NULL) = derived data dasar, hanya dari source data dasar.
    */
    private function getEffectiveSource($wilayahId, $periodeId, $jenisTabelId, $putaranId = null): Collection
    {
        if ($putaranId !== null && !Putaran::whereKey($putaranId)->exists()) {
            throw new \InvalidArgumentException('Putaran untuk perhitungan derived tidak ditemukan.');
        }

        return app(EffectiveValueService::class)
            ->source(
                PdrbModul::PENGELUARAN,
                $wilayahId,
                [$periodeId],
                [$jenisTabelId],
                $putaranId === null ? 0 : (int) $putaranId
            )
            ->keyBy(fn ($item) => (string) $item->kategori_pengeluaran_id);
    }

    /*
    |--------------------------------------------------------------------------
    | IMPLISIT EFEKTIF
    |--------------------------------------------------------------------------
    | Hitung langsung dari ADHB/ADHK efektif agar perbandingan periode tidak
    | tergantung pada derived periode pembanding yang mungkin belum digenerate.
    */
    private function getEffectiveImplicitValues($wilayahId, $periodeId, $putaranId = null): Collection
    {
        $adhb = $this->getEffectiveSource($wilayahId, $periodeId, 1, $putaranId);
        $adhk = $this->getEffectiveSource($wilayahId, $periodeId, 2, $putaranId);
        $result = collect();

        foreach ($adhb as $key => $item) {
            $dataAdhk = $adhk->get($key);
            if (!$dataAdhk) {
                continue;
            }

            $nilai = $dataAdhk->nilai == 0
                ? 0
                : ($item->nilai / $dataAdhk->nilai) * 100;

            $result[$key] = [
                'kategori_pengeluaran_id' => $item->kategori_pengeluaran_id,
                'nilai' => round($nilai, 6),
            ];
        }

        return $result;
    }

    private function makeDerivedRow($wilayahId, $periodeId, $jenisTabelId, $kategoriId, $nilai, $putaranId): array
    {
        return [
            'submission_id' => null,
            'putaran_id' => $putaranId,
            'wilayah_id' => $wilayahId,
            'periode_id' => $periodeId,
            'jenis_tabel_id' => $jenisTabelId,
            'kategori_pengeluaran_id' => $kategoriId,
            'nilai' => round($nilai, 6),
            'tipe_data' => 'derived',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /* Hanya hasil untuk versi yang sedang dihitung boleh diganti. */
    private function persistDerived($wilayahId, $periodeId, $jenisTabelId, $putaranId, array $rows): void
    {
        DB::transaction(function () use ($wilayahId, $periodeId, $jenisTabelId, $putaranId, $rows) {
            $query = DataPdrbPengeluaran::where([
                'wilayah_id' => $wilayahId,
                'periode_id' => $periodeId,
                'jenis_tabel_id' => $jenisTabelId,
                'tipe_data' => 'derived',
            ]);

            if ($putaranId === null) {
                $query->whereNull('putaran_id');
            } else {
                $query->where('putaran_id', $putaranId);
            }

            $query->delete();
            if (!empty($rows)) {
                DataPdrbPengeluaran::insert($rows);
            }
        });
    }

    private function getPeriodeQtQ($periodeId)
    {
        $periode = Periode::find($periodeId);
        if (!$periode) {
            return null;
        }
        if ($periode->triwulan == 1) {
            return Periode::where('tahun', $periode->tahun - 1)
                ->where('triwulan', 4)->first();
        }
        return Periode::where('tahun', $periode->tahun)
            ->where('triwulan', $periode->triwulan - 1)->first();
    }

    private function getPeriodeYtY($periodeId)
    {
        $periode = Periode::find($periodeId);
        if (!$periode) {
            return null;
        }
        return Periode::where('tahun', $periode->tahun - 1)
            ->where('triwulan', $periode->triwulan)->first();
    }

    private function getPeriodeCTC($tahun, $triwulan)
    {
        return Periode::where('tahun', $tahun)
            ->where('triwulan', '<=', $triwulan)->get();
    }

    public function hitungDistribusi($wilayahId, $periodeId, $putaranId = null): void
    {
        $source = $this->getEffectiveSource($wilayahId, $periodeId, 1, $putaranId);
        if ($source->isEmpty()) {
            return;
        }

        // Tetap sesuai rumus dan cara menentukan total pada service lama.
        $totalPdrb = $source->filter(function ($item) {
            return $item->kategori && $item->kategori->parent_id === null;
        })->sum('nilai');
        if ($totalPdrb == 0) {
            return;
        }

        $rows = [];
        foreach ($source as $item) {
            $nilai = ($item->nilai / $totalPdrb) * 100;
            $rows[] = $this->makeDerivedRow(
                $wilayahId, $periodeId, 3, $item->kategori_pengeluaran_id, $nilai, $putaranId
            );
        }
        $this->persistDerived($wilayahId, $periodeId, 3, $putaranId, $rows);
    }

    public function hitungQtQ($wilayahId, $periodeId, $putaranId = null): void
    {
        $sebelumnya = $this->getPeriodeQtQ($periodeId);
        if (!$sebelumnya) {
            return;
        }
        $sekarang = $this->getEffectiveSource($wilayahId, $periodeId, 2, $putaranId);
        if ($sekarang->isEmpty()) {
            return;
        }
        $lalu = $this->getEffectiveSource($wilayahId, $sebelumnya->id, 2, $putaranId);
        $rows = [];
        foreach ($sekarang as $key => $item) {
            $old = $lalu->get($key);
            $nilai = !$old || $old->nilai == 0
                ? 0 : (($item->nilai / $old->nilai) * 100) - 100;
            $rows[] = $this->makeDerivedRow(
                $wilayahId, $periodeId, 4, $item->kategori_pengeluaran_id, $nilai, $putaranId
            );
        }
        $this->persistDerived($wilayahId, $periodeId, 4, $putaranId, $rows);
    }

    public function hitungYtY($wilayahId, $periodeId, $putaranId = null): void
    {
        $sebelumnya = $this->getPeriodeYtY($periodeId);
        if (!$sebelumnya) {
            return;
        }
        $sekarang = $this->getEffectiveSource($wilayahId, $periodeId, 2, $putaranId);
        if ($sekarang->isEmpty()) {
            return;
        }
        $lalu = $this->getEffectiveSource($wilayahId, $sebelumnya->id, 2, $putaranId);
        $rows = [];
        foreach ($sekarang as $key => $item) {
            $old = $lalu->get($key);
            $nilai = !$old || $old->nilai == 0
                ? 0 : (($item->nilai / $old->nilai) * 100) - 100;
            $rows[] = $this->makeDerivedRow(
                $wilayahId, $periodeId, 5, $item->kategori_pengeluaran_id, $nilai, $putaranId
            );
        }
        $this->persistDerived($wilayahId, $periodeId, 5, $putaranId, $rows);
    }

    public function hitungCtC($wilayahId, $periodeId, $putaranId = null): void
    {
        $periode = Periode::find($periodeId);
        if (!$periode) {
            return;
        }

        $periodeSekarang = $this->getPeriodeCTC($periode->tahun, $periode->triwulan);
        $periodeLalu = $this->getPeriodeCTC($periode->tahun - 1, $periode->triwulan);
        $sekarang = [];
        $lalu = [];

        foreach ($periodeSekarang as $p) {
            $source = $this->getEffectiveSource($wilayahId, $p->id, 2, $putaranId);
            foreach ($source as $key => $item) {
                if (!isset($sekarang[$key])) {
                    $sekarang[$key] = ['kategori' => $item->kategori_pengeluaran_id, 'nilai' => 0];
                }
                $sekarang[$key]['nilai'] += $item->nilai;
            }
        }

        foreach ($periodeLalu as $p) {
            $source = $this->getEffectiveSource($wilayahId, $p->id, 2, $putaranId);
            foreach ($source as $key => $item) {
                if (!isset($lalu[$key])) {
                    $lalu[$key] = ['kategori' => $item->kategori_pengeluaran_id, 'nilai' => 0];
                }
                $lalu[$key]['nilai'] += $item->nilai;
            }
        }

        $rows = [];
        foreach ($sekarang as $key => $item) {
            if (!isset($lalu[$key])) {
                continue;
            }
            $totalLalu = $lalu[$key]['nilai'];
            $nilai = $totalLalu == 0
                ? 0 : (($item['nilai'] / $totalLalu) * 100) - 100;
            $rows[] = $this->makeDerivedRow(
                $wilayahId, $periodeId, 6, $item['kategori'], $nilai, $putaranId
            );
        }
        $this->persistDerived($wilayahId, $periodeId, 6, $putaranId, $rows);
    }

    public function hitungImplisit($wilayahId, $periodeId, $putaranId = null): void
    {
        $source = $this->getEffectiveImplicitValues($wilayahId, $periodeId, $putaranId);
        if ($source->isEmpty()) {
            return;
        }
        $rows = [];
        foreach ($source as $item) {
            $rows[] = $this->makeDerivedRow(
                $wilayahId, $periodeId, 7, $item['kategori_pengeluaran_id'], $item['nilai'], $putaranId
            );
        }
        $this->persistDerived($wilayahId, $periodeId, 7, $putaranId, $rows);
    }

    public function hitungImplisitQtQ($wilayahId, $periodeId, $putaranId = null): void
    {
        $periodeSebelumnya = $this->getPeriodeQtQ($periodeId);
        if (!$periodeSebelumnya) {
            return;
        }
        $sekarang = $this->getEffectiveImplicitValues($wilayahId, $periodeId, $putaranId);
        if ($sekarang->isEmpty()) {
            return;
        }
        $lalu = $this->getEffectiveImplicitValues($wilayahId, $periodeSebelumnya->id, $putaranId);
        $rows = [];
        foreach ($sekarang as $key => $item) {
            $old = $lalu->get($key);
            $nilai = !$old || $old['nilai'] == 0
                ? 0 : (($item['nilai'] / $old['nilai']) * 100) - 100;
            $rows[] = $this->makeDerivedRow(
                $wilayahId, $periodeId, 8, $item['kategori_pengeluaran_id'], $nilai, $putaranId
            );
        }
        $this->persistDerived($wilayahId, $periodeId, 8, $putaranId, $rows);
    }

    public function hitungImplisitYtY($wilayahId, $periodeId, $putaranId = null): void
    {
        $periodeSebelumnya = $this->getPeriodeYtY($periodeId);
        if (!$periodeSebelumnya) {
            return;
        }
        $sekarang = $this->getEffectiveImplicitValues($wilayahId, $periodeId, $putaranId);
        if ($sekarang->isEmpty()) {
            return;
        }
        $lalu = $this->getEffectiveImplicitValues($wilayahId, $periodeSebelumnya->id, $putaranId);
        $rows = [];
        foreach ($sekarang as $key => $item) {
            $old = $lalu->get($key);
            $nilai = !$old || $old['nilai'] == 0
                ? 0 : (($item['nilai'] / $old['nilai']) * 100) - 100;
            $rows[] = $this->makeDerivedRow(
                $wilayahId, $periodeId, 9, $item['kategori_pengeluaran_id'], $nilai, $putaranId
            );
        }
        $this->persistDerived($wilayahId, $periodeId, 9, $putaranId, $rows);
    }
}

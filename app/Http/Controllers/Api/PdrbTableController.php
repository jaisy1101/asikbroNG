<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Rekonsiliasi;
use App\Models\Periode;
use App\Models\DataPdrbLapanganUsaha;
use App\Models\DataPdrbPengeluaran;
use App\Models\JenisTabelPdrb;
use App\Models\KategoriLapanganUsaha;
use App\Models\KategoriPengeluaran;
use App\Models\Putaran;
use App\Models\RekonsiliasiPeriode;
use App\Services\Pdrb\EffectiveValueService;
use App\Services\Pdrb\PdrbModul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PdrbTableController extends Controller
{
    public function showSubmission($id)
    {
        $submission = Submission::findOrFail($id);


        if ($submission->modul_id == 1) {

            $data = $submission
                ->dataPdrbLapanganUsaha()
                ->with([
                    'periode',
                    'jenisTabel',
                    'kategori'
                ])
                ->get();


            $result = $data
                ->groupBy('kategori.nama')
                ->map(function ($items) {

                    return [
                        'kategori' => $items->first()->kategori->nama,

                        'data' => $items->map(function ($item) {

                            return [
                                'periode' =>
                                    $item->periode->tahun .
                                    ' Q' .
                                    $item->periode->triwulan,

                                'jenis_tabel_id' =>
                                    $item->jenis_tabel_id,

                                'nilai' =>
                                    $item->nilai,
                            ];

                        })->values()
                    ];

                })->values();


        } elseif ($submission->modul_id == 2) {


            $data = $submission
                ->dataPdrbPengeluaran()
                ->with([
                    'periode',
                    'jenisTabel',
                    'kategori'
                ])
                ->get();



            $result = $data
                ->groupBy('kategori.nama')
                ->map(function ($items) {


                    return [
                        'kategori' => $items->first()->kategori->nama,

                        'data' => $items->map(function ($item) {


                            return [
                                'periode' =>
                                    $item->periode->tahun .
                                    ' Q' .
                                    $item->periode->triwulan,

                                'jenis_tabel_id' =>
                                    $item->jenis_tabel_id,

                                'nilai' =>
                                    $item->nilai,
                            ];


                        })->values()
                    ];


                })->values();

        } else {


            return response()->json([
                'message' => 'Modul tidak ditemukan'
            ], 400);


        }



        return response()->json([

            'submission_id' => $submission->id,

            'modul_id' => $submission->modul_id,

            'table' => $result

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | TABEL PDRB BERDASARKAN NILAI EFEKTIF
    |--------------------------------------------------------------------------
    |
    | Tanpa ?putaran_id : kondisi terbaru (termasuk putaran yang berlangsung).
    | Dengan ?putaran_id: histori, nilai efektif pada putaran tersebut.
    |
    | Periode dibatasi sampai triwulan rekonsiliasi terkait (rekonsiliasi
    | berlangsung untuk kondisi terbaru, rekonsiliasi putaran untuk histori).
    |
    | periode_terbuka: periode yang boleh diunggah/diedit, hanya terisi pada
    | kondisi terbaru ketika ada putaran berlangsung.
    |
    */

    public function showLapanganUsaha(Request $request, $wilayah_id, $jenis_tabel_id)
    {
        return $this->tabel(
            $request,
            PdrbModul::LAPANGAN_USAHA,
            $wilayah_id,
            $jenis_tabel_id
        );
    }

    public function showPengeluaran(Request $request, $wilayah_id, $jenis_tabel_id)
    {
        return $this->tabel(
            $request,
            PdrbModul::PENGELUARAN,
            $wilayah_id,
            $jenis_tabel_id
        );
    }


    private function tabel(Request $request, int $modulId, $wilayah_id, $jenis_tabel_id)
    {
        if (Auth::check() && Auth::user()->role_id == 2) {
            $wilayah_id = Auth::user()->wilayah_id;
        }

        $putaranId = $request->filled('putaran_id')
            ? (int) $request->query('putaran_id')
            : null;

        $periodeTerbuka = [];

        if ($putaranId !== null) {

            $putaran = Putaran::with('rekonsiliasi.periode')->find($putaranId);

            if (!$putaran) {
                return response()->json([
                    'message' => 'Putaran tidak ditemukan',
                ], 404);
            }

            $periodeBatas = $putaran->rekonsiliasi?->periode;

        } else {

            $rekonsiliasi = Rekonsiliasi::where('status', 'berlangsung')
                ->latest('id')
                ->first();

            $periodeBatas = $rekonsiliasi?->periode;

            $putaranAktif = $rekonsiliasi
                ? Putaran::where('rekonsiliasi_id', $rekonsiliasi->id)
                    ->where('status', 'berlangsung')
                    ->first()
                : null;

            if ($putaranAktif) {
                $periodeTerbuka = RekonsiliasiPeriode::where('rekonsiliasi_id', $rekonsiliasi->id)
                    ->with('periode')
                    ->get()
                    ->sortBy(fn ($item) => [$item->periode->tahun, $item->periode->triwulan])
                    ->map(fn ($item) => $this->labelPeriode($item->periode))
                    ->values()
                    ->all();
            }
        }

        $periodeList = Periode::query()
            ->when($periodeBatas, function ($query) use ($periodeBatas) {
                $query->where(function ($q) use ($periodeBatas) {
                    $q->where('tahun', '<', $periodeBatas->tahun)
                        ->orWhere(function ($q2) use ($periodeBatas) {
                            $q2->where('tahun', $periodeBatas->tahun)
                                ->where('triwulan', '<=', $periodeBatas->triwulan);
                        });
                });
            })
            ->get()
            ->keyBy('id');

        $tipeData = JenisTabelPdrb::whereKey($jenis_tabel_id)->value('tipe_data') ?? 'source';

        $effective = app(EffectiveValueService::class);

        $data = $tipeData === 'derived'
            ? $effective->derived($modulId, $wilayah_id, $periodeList->keys()->all(), [$jenis_tabel_id], $putaranId)
            : $effective->source($modulId, $wilayah_id, $periodeList->keys()->all(), [$jenis_tabel_id], $putaranId);

        $kategoriModel = $modulId === PdrbModul::LAPANGAN_USAHA
            ? KategoriLapanganUsaha::class
            : KategoriPengeluaran::class;

        $kategoriList = $kategoriModel::all()->keyBy('id');

        $kategoriColumn = PdrbModul::kategoriColumn($modulId);

        $result = $data
            ->groupBy(fn ($item) => PdrbModul::kategoriKey($item->{$kategoriColumn}))
            ->map(function ($items, $kategoriKey) use ($kategoriList, $periodeList) {

                $row = [];

                $kategori = $kategoriList->get($kategoriKey);

                if ($kategori === null) {

                    $row['kategori'] = 'TOTAL';

                    $row['kode'] = null;

                    $row['level'] = 0;

                } else {

                    $row['kategori'] = $kategori->nama;

                    $row['kode'] = $kategori->kode;

                    $row['level'] = $kategori->level;

                }

                foreach ($items as $item) {

                    $row[$this->labelPeriode($periodeList[$item->periode_id])] = $item->nilai;

                }

                $row['_urutan'] = $kategori
                    ? [$kategori->urutan, $kategori->id]
                    : [-1, 0];

                return $row;

            })
            ->sortBy('_urutan')
            ->map(function ($row) {
                unset($row['_urutan']);
                return $row;
            })
            ->values();


        /*
        | Baris TOTAL (ADHB/ADHK saja) = jumlah kategori induk per periode,
        | sama dengan TOTAL di Konserda dan Integrasi. Nilai kosong = 0;
        | periode tanpa data sama sekali tetap kosong. Derived tidak
        | dijumlahkan karena perhitungannya berbeda.
        */
        if ($tipeData === 'source') {

            $indukIds = $kategoriList->whereNull('parent_id')->keys()->all();

            $total = [
                'kategori' => 'TOTAL',
                'kode' => null,
                'level' => 0,
            ];

            foreach ($data as $item) {

                if (!in_array($item->{$kategoriColumn}, $indukIds)) {
                    continue;
                }

                $label = $this->labelPeriode($periodeList[$item->periode_id]);

                $total[$label] = ($total[$label] ?? 0) + (float) $item->nilai;
            }

            $result = $result
                ->reject(fn ($row) => $row['kode'] === null && $row['kategori'] === 'TOTAL')
                ->prepend($total)
                ->values();
        }



        return response()->json([

            'wilayah_id' => $wilayah_id,

            'jenis_tabel_id' => $jenis_tabel_id,

            'putaran_id' => $putaranId,

            'periode_terbuka' => $periodeTerbuka,

            'table' => $result

        ]);

    }


    private function labelPeriode($periode): string
    {
        return $periode->tahun . ' Q' . $periode->triwulan;
    }

}
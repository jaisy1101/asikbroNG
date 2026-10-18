<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilKonserda;
use App\Models\KategoriLapanganUsaha;
use App\Models\KategoriPengeluaran;
use App\Models\Periode;
use App\Models\Wilayah;
use App\Services\Pdrb\PdrbModul;
use Illuminate\Support\Facades\DB;
use App\Models\Putaran;
use Illuminate\Support\Facades\Auth;

class KonserdaController extends Controller
{
    private function cekAksesKonserda($putaranId)
    {

        $user = Auth::user();


        $putaran = Putaran::findOrFail($putaranId);


        // admin bebas melihat
        if($user->role_id == 1){

            return;

        }


        // user hanya jika putaran selesai
        if($putaran->status != 'selesai'){

            abort(response()->json([

                'message'=>'Hasil konserda belum dapat diakses'

            ],403));

        }

    }

    public function index($putaranId)
    {
        $this->cekAksesKonserda($putaranId);

        $data = HasilKonserda::with([

            'wilayahParent',
            'periode',
            'kategori',
            'detail.wilayah',

        ])
        ->where(
            'putaran_id',
            $putaranId
        )
        ->orderBy('modul_id')
        ->orderBy('jenis_tabel_id')
        ->orderByRaw('kategori_id IS NOT NULL')
        ->orderBy('kategori_id')
        ->get()
        ->map(function ($item) {

            if ($item->kategori_id == null) {

                $item->kategori = [
                    'nama' => 'TOTAL',
                    'kode' => null,
                    'level' => 0,
                ];

            }

            return $item;

        });


        return response()->json([

            'putaran_id' => $putaranId,

            'data' => $data

        ]);

    }

    public function showLapanganUsaha($putaranId, $jenis_tabel_id)
    {
        $this->cekAksesKonserda($putaranId);

        return response()->json(
            $this->hasilTabel((int) $putaranId, PdrbModul::LAPANGAN_USAHA, (int) $jenis_tabel_id)
        );
    }

    public function showPengeluaran($putaranId, $jenis_tabel_id)
    {
        $this->cekAksesKonserda($putaranId);

        return response()->json(
            $this->hasilTabel((int) $putaranId, PdrbModul::PENGELUARAN, (int) $jenis_tabel_id)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DATA TABEL HASIL KONSERDA
    |--------------------------------------------------------------------------
    |
    | Hanya kolom yang dipakai halaman, tanpa model Eloquent (puluhan ribu
    | baris detail). Daftar kab/kota dikirim sekali di "wilayah", bukan di
    | setiap detail. Kategori diambil dari tabel kategori modul yang sesuai.
    |
    */
    private function hasilTabel(int $putaranId, int $modulId, int $jenisTabelId): array
    {
        $hasil = DB::table('hasil_konserda')
            ->where('putaran_id', $putaranId)
            ->where('modul_id', $modulId)
            ->where('jenis_tabel_id', $jenisTabelId)
            ->get([
                'id', 'periode_id', 'kategori_id', 'nilai_provinsi',
                'nilai_agregasi_kabkota', 'selisih', 'diskrepansi_persen', 'status',
            ]);

        $periode = Periode::whereIn('id', $hasil->pluck('periode_id')->unique())
            ->get(['id', 'tahun', 'triwulan'])
            ->keyBy('id');

        $kategoriModel = $modulId === PdrbModul::LAPANGAN_USAHA
            ? KategoriLapanganUsaha::class
            : KategoriPengeluaran::class;

        $kategori = $kategoriModel::whereIn('id', $hasil->pluck('kategori_id')->filter()->unique())
            ->get(['id', 'kode', 'nama', 'level'])
            ->keyBy('id');

        $detail = DB::table('hasil_konserda_details')
            ->whereIn('hasil_konserda_id', $hasil->pluck('id'))
            ->orderBy('id')
            ->get(['hasil_konserda_id', 'wilayah_id', 'nilai'])
            ->groupBy('hasil_konserda_id');

        $data = $hasil
            // TOTAL lebih dulu, lalu kategori; kemudian urut periode.
            ->sortBy(fn ($item) => [
                $item->kategori_id === null ? 0 : 1,
                (int) $item->kategori_id,
            ])
            ->sortBy(fn ($item) => [
                $periode[$item->periode_id]->tahun,
                $periode[$item->periode_id]->triwulan,
            ])
            ->values()
            ->map(function ($item) use ($periode, $kategori, $detail) {

                $item->periode = $periode[$item->periode_id];

                $item->kategori = $item->kategori_id === null
                    ? ['nama' => 'TOTAL', 'kode' => null, 'level' => 0]
                    : $kategori->get($item->kategori_id);

                $item->detail = ($detail->get($item->id) ?? collect())
                    ->map(fn ($d) => ['wilayah_id' => $d->wilayah_id, 'nilai' => $d->nilai])
                    ->values();

                unset($item->id);

                return $item;

            });

        $wilayah = Wilayah::whereIn(
            'id',
            $detail->flatten(1)->pluck('wilayah_id')->unique()
        )
            ->orderBy('id')
            ->get(['id', 'nama']);

        return [
            'putaran_id' => $putaranId,
            'jenis_tabel_id' => $jenisTabelId,
            'wilayah' => $wilayah,
            'data' => $data,
        ];
    }
}
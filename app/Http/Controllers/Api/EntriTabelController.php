<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriLapanganUsaha;
use App\Models\KategoriPengeluaran;
use App\Models\Putaran;
use App\Models\RekonsiliasiPeriode;
use App\Models\Submission;
use App\Models\Wilayah;
use App\Services\Pdrb\EffectiveValueService;
use App\Services\Pdrb\PdrbExcelExport;
use App\Services\Pdrb\PdrbModul;
use App\Services\Pdrb\PenyimpananDitolakException;
use App\Services\Pdrb\SourceWriter;
use App\Services\Pdrb\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| ENTRI TABEL - ISI TABEL
|--------------------------------------------------------------------------
|
| Input per sel untuk wilayah user yang login, pada putaran berlangsung.
| Nilai awal = nilai efektif saat ini; penyimpanan memakai SourceWriter
| yang sama dengan unggah berkas.
|
*/

class EntriTabelController extends Controller
{
    private const JENIS_TABEL = [1 => 'ADHB', 2 => 'ADHK'];

    // Induk yang nilainya = anak pertama - anak kedua (bukan penjumlahan).
    private const INDUK_SELISIH = [
        PdrbModul::PENGELUARAN => ['H'],
    ];


    public function __construct(
        private SubmissionService $submissions,
        private EffectiveValueService $effective,
        private SourceWriter $writer
    ) {
    }


    public function show(int $modulId)
    {
        $putaran = $this->submissions->putaranBerlangsung();

        if (!$putaran) {
            return $this->tidakAdaPutaran();
        }

        $putaran->load('rekonsiliasi.periode');

        $wilayahId = Auth::user()->wilayah_id;

        $periodeList = $this->periodeTerbuka($putaran);

        $kategoriList = $this->kategori($modulId);

        $kategoriColumn = PdrbModul::kategoriColumn($modulId);

        $data = $this->effective->source(
            $modulId,
            $wilayahId,
            $periodeList->pluck('id')->all(),
            array_keys(self::JENIS_TABEL),
            $putaran->id
        );

        // Siapa yang mengubah sel pada putaran ini (untuk tooltip).
        $pengubah = Submission::with('user:id,name,username')
            ->whereIn(
                'id',
                $data->where('putaran_id', $putaran->id)->pluck('submission_id')->filter()->unique()
            )
            ->get()
            ->keyBy('id');

        $nilai = [];

        foreach (array_keys(self::JENIS_TABEL) as $jenisTabelId) {
            $nilai[$jenisTabelId] = (object) [];
        }

        foreach ($data as $row) {

            if ($row->{$kategoriColumn} === null) {
                continue;
            }

            $diubah = (int) $row->putaran_id === (int) $putaran->id;

            $submission = $diubah ? $pengubah->get($row->submission_id) : null;

            $nilai[$row->jenis_tabel_id]->{$row->periode_id . ':' . $row->{$kategoriColumn}} = [
                'nilai' => (float) $row->nilai,
                'diubah' => $diubah,
                'oleh' => $submission?->user?->name ?? $submission?->user?->username,
                'waktu' => $diubah ? $row->updated_at?->toIso8601String() : null,
            ];
        }

        return response()->json([

            'rekonsiliasi' => [
                'nama' => $putaran->rekonsiliasi->nama,
                'tahun' => $putaran->rekonsiliasi->periode->tahun,
                'triwulan' => $putaran->rekonsiliasi->periode->triwulan,
            ],

            'putaran' => [
                'id' => $putaran->id,
                'nomor' => $putaran->nomor,
            ],

            'wilayah' => Wilayah::select('id', 'nama')->find($wilayahId),

            'jenis_tabel' => collect(self::JENIS_TABEL)
                ->map(fn ($kode, $id) => ['id' => $id, 'kode' => $kode])
                ->values(),

            'periode' => $periodeList->map(fn ($periode) => [
                'id' => $periode->id,
                'label' => $periode->tahun . ' Q' . $periode->triwulan,
            ]),

            'kategori' => $this->denganAturanInduk($modulId, $kategoriList),

            // nilai[jenis_tabel_id]["periode_id:kategori_id"]
            'nilai' => $nilai,

        ]);
    }


    /*
    | Format body:
    |   { "nilai": { "1": [ {periode_id, kategori_id, nilai}, ... ], "2": [ ... ] } }
    |
    | Seluruh sel tabel dikirim seperti satu berkas Excel; sel kosong
    | (null) disimpan sebagai 0. Sel yang nilainya sama tidak mengubah data.
    */
    public function store(Request $request, int $modulId)
    {
        $request->validate([
            'nilai' => 'required|array',
            'nilai.*' => 'array',
            'nilai.*.*.periode_id' => 'required|integer',
            'nilai.*.*.kategori_id' => 'required|integer',
            'nilai.*.*.nilai' => 'nullable|numeric',
        ]);

        $input = $request->input('nilai');

        $jenisTidakDikenal = array_diff(
            array_map('intval', array_keys($input)),
            array_keys(self::JENIS_TABEL)
        );

        $kategoriIds = $this->kategori($modulId)->pluck('id')->all();

        $kategoriTidakDikenal = collect($input)
            ->flatten(1)
            ->pluck('kategori_id')
            ->map(fn ($id) => (int) $id)
            ->diff($kategoriIds);

        if (!empty($jenisTidakDikenal) || $kategoriTidakDikenal->isNotEmpty()) {
            return response()->json([
                'message' => 'Jenis tabel atau kategori tidak dikenal.',
            ], 422);
        }

        $putaran = $this->submissions->putaranBerlangsung();

        if (!$putaran) {
            return $this->tidakAdaPutaran();
        }

        try {

            DB::beginTransaction();

            $submission = $this->submissions->buat(
                Auth::user(),
                $putaran,
                $modulId,
                'form'
            );

            $hasil = [];

            foreach ($input as $jenisTabelId => $cells) {

                $hasil[] = $this->writer->simpan(
                    $submission,
                    (int) $jenisTabelId,
                    array_map(fn ($cell) => [
                        'periode_id' => (int) $cell['periode_id'],
                        'kategori_id' => (int) $cell['kategori_id'],
                        'nilai' => $cell['nilai'] ?? null,
                    ], $cells)
                );
            }

            $hasil = SourceWriter::gabungHasil(...$hasil);

            $this->submissions->catatHasil($submission, $hasil);

            DB::commit();

        } catch (PenyimpananDitolakException $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('SIMPAN ISI TABEL GAGAL', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Data gagal disimpan',
                'error' => $e->getMessage(),
            ], 500);
        }

        $this->submissions->prosesLanjutan($submission, $hasil);

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'submission_id' => $submission->id,
            'jumlah_perubahan' => $hasil['jumlah_perubahan'],
        ]);
    }


    /*
    | Template Excel untuk Unggah Berkas: periode terbuka saja, berisi
    | nilai yang berlaku saat ini untuk wilayah user.
    */
    public function template(int $modulId, PdrbExcelExport $excel)
    {
        $putaran = $this->submissions->putaranBerlangsung();

        if (!$putaran) {
            return $this->tidakAdaPutaran();
        }

        $wilayah = Wilayah::findOrFail(Auth::user()->wilayah_id);

        $path = $excel->simpanSementara(
            $excel->buat($modulId, $wilayah, $putaran, false)
        );

        return response()
            ->download($path, $excel->namaFile($modulId, $wilayah, $putaran))
            ->deleteFileAfterSend();
    }


    private function tidakAdaPutaran()
    {
        return response()->json([
            'message' => 'Entri tabel hanya tersedia saat ada putaran rekonsiliasi yang berlangsung.',
        ], 409);
    }


    private function periodeTerbuka(Putaran $putaran)
    {
        return RekonsiliasiPeriode::where('rekonsiliasi_id', $putaran->rekonsiliasi_id)
            ->with('periode')
            ->get()
            ->pluck('periode')
            ->sortBy(fn ($periode) => [$periode->tahun, $periode->triwulan])
            ->values();
    }


    private function kategori(int $modulId)
    {
        $model = $modulId === PdrbModul::LAPANGAN_USAHA
            ? KategoriLapanganUsaha::class
            : KategoriPengeluaran::class;

        return $model::orderBy('urutan')
            ->orderBy('id')
            ->get(['id', 'kode', 'nama', 'level', 'parent_id']);
    }


    /*
    | Aturan pengecekan induk untuk frontend (hanya peringatan):
    |   jumlah  : induk = jumlah anak
    |   selisih : induk = anak pertama - anak kedua (H Net Ekspor)
    */
    private function denganAturanInduk(int $modulId, $kategoriList)
    {
        $anak = $kategoriList->groupBy('parent_id');

        return $kategoriList->map(function ($kategori) use ($modulId, $anak) {

            $daftarAnak = $anak->get($kategori->id);

            $aturan = null;

            if ($daftarAnak) {

                $selisih = in_array(
                    $kategori->kode,
                    self::INDUK_SELISIH[$modulId] ?? [],
                    true
                ) && $daftarAnak->count() === 2;

                $aturan = [
                    'rumus' => $selisih ? 'selisih' : 'jumlah',
                    'anak' => $daftarAnak->pluck('id')->values(),
                ];
            }

            return [
                'id' => $kategori->id,
                'kode' => $kategori->kode,
                'nama' => $kategori->nama,
                'level' => $kategori->level,
                'aturan_induk' => $aturan,
            ];
        });
    }
}

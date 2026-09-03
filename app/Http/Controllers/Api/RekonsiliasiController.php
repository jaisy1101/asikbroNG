<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rekonsiliasi;
use App\Models\Putaran;
use App\Models\Periode;
use Illuminate\Support\Facades\DB;

class RekonsiliasiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | BUKA QUARTAL BARU
    |--------------------------------------------------------------------------
    | Admin memilih tahun + triwulan.
    | Sistem membuat Rekonsiliasi baru + Putaran 0.
    */
    public function bukaQuartalBaru(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer',
            'triwulan' => 'required|integer|between:1,4',
        ]);

        /*
        | SEMENTARA
        */
        $adminId = 1;

        /*
        | NANTI SETELAH MIDDLEWARE AKTIF:
        | $adminId = $request->user()->id;
        */

        try {

            DB::beginTransaction();

            $periode = Periode::where('tahun', $request->tahun)
                ->where('triwulan', $request->triwulan)
                ->first();

            if (!$periode) {

                DB::rollBack();

                return response()->json([
                    'message' => 'Periode tidak ditemukan',
                ], 404);
            }

            $rekonsiliasiAktif = Rekonsiliasi::where('status', 'berlangsung')
                ->latest('id')
                ->first();

            if ($rekonsiliasiAktif) {

                $rekonsiliasiAktif->update([
                    'status' => 'selesai',
                    'tanggal_selesai' => now(),
                ]);

                Putaran::where('rekonsiliasi_id', $rekonsiliasiAktif->id)
                    ->where('status', 'berlangsung')
                    ->update([
                        'status' => 'selesai',
                        'tanggal_selesai' => now(),
                    ]);
            }

            $rekonsiliasi = Rekonsiliasi::create([
                'periode_id' => $periode->id,
                'dibuat_oleh' => $adminId,
                'nama' => "Rekonsiliasi PDRB {$request->tahun} Q{$request->triwulan}",
                'status' => 'berlangsung',
                'tanggal_mulai' => now(),
            ]);

            $putaran = Putaran::create([
                'rekonsiliasi_id' => $rekonsiliasi->id,
                'nomor' => 0,
                'status' => 'berlangsung',
                'tanggal_mulai' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Quartal baru berhasil dibuka',
                'rekonsiliasi_id' => $rekonsiliasi->id,
                'putaran_id' => $putaran->id,
                'tahun' => $request->tahun,
                'triwulan' => $request->triwulan,
                'putaran' => 0,
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal membuka quartal baru',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BUKA PUTARAN BARU
    |--------------------------------------------------------------------------
    | Tidak perlu input tahun, triwulan, atau nomor putaran.
    | Sistem otomatis membaca rekonsiliasi dan putaran terakhir.
    */
    public function bukaPutaranBaru()
    {
        try {

            DB::beginTransaction();

            $rekonsiliasi = Rekonsiliasi::where('status', 'berlangsung')
                ->latest('id')
                ->first();

            if (!$rekonsiliasi) {

                DB::rollBack();

                return response()->json([
                    'message' => 'Tidak ada rekonsiliasi yang sedang berlangsung',
                ], 404);
            }

            $putaranTerakhir = Putaran::where(
                'rekonsiliasi_id',
                $rekonsiliasi->id
            )
                ->orderByDesc('nomor')
                ->first();

            if (!$putaranTerakhir) {

                DB::rollBack();

                return response()->json([
                    'message' => 'Putaran belum tersedia',
                ], 404);
            }

            $nomorBaru = $putaranTerakhir->nomor + 1;

            $putaranTerakhir->update([
                'status' => 'selesai',
                'tanggal_selesai' => now(),
            ]);

            $putaranBaru = Putaran::create([
                'rekonsiliasi_id' => $rekonsiliasi->id,
                'nomor' => $nomorBaru,
                'status' => 'berlangsung',
                'tanggal_mulai' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Putaran baru berhasil dibuka',
                'rekonsiliasi_id' => $rekonsiliasi->id,
                'putaran_id' => $putaranBaru->id,
                'nomor_putaran' => $nomorBaru,
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal membuka putaran baru',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TUTUP REKONSILIASI
    |--------------------------------------------------------------------------
    */
    public function tutup()
    {
        $rekonsiliasi = Rekonsiliasi::where('status', 'berlangsung')
            ->latest('id')
            ->first();

        if (!$rekonsiliasi) {
            return response()->json([
                'message' => 'Tidak ada rekonsiliasi yang sedang berlangsung',
            ], 404);
        }

        $putaran = Putaran::where('rekonsiliasi_id', $rekonsiliasi->id)
            ->where('status', 'berlangsung')
            ->latest('nomor')
            ->first();

        if (!$putaran) {
            return response()->json([
                'message' => 'Tidak ada putaran yang sedang berlangsung',
            ], 404);
        }

        $putaran->update([
            'status' => 'selesai',
            'tanggal_selesai' => now(),
        ]);

        return response()->json([
            'message' => 'Putaran berhasil ditutup',
            'rekonsiliasi_id' => $rekonsiliasi->id,
            'putaran_id' => $putaran->id,
            'nomor_putaran' => $putaran->nomor,
        ], 200);
    }
}
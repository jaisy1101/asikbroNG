<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Wilayah;
use App\Models\IntegrasiPdrb;
use App\Models\Putaran;
use App\Services\Pdrb\PdrbExcelExport;
use App\Services\Pdrb\PdrbModul;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MonitoringController extends Controller
{

    public function index($putaranId, $modulId)
    {

        $user = Auth::user();


        if($user->role_id != 1){

            return response()->json([
                'message'=>'Akses ditolak'
            ],403);

        }


        $wilayahList = Wilayah::whereIn(
            'jenis',
            [
                'kabupaten',
                'kota'
            ]
        )
        ->get();



        $data = [];


        $wilayahDiubah = $this->wilayahDiubah($putaranId, (int) $modulId);



        foreach($wilayahList as $wilayah){



            /*
            |--------------------------------------------------------------------------
            | SUBMISSION
            |--------------------------------------------------------------------------
            */

            $submission = Submission::with('files')
            ->where(
                'putaran_id',
                $putaranId
            )
            ->where(
                'wilayah_id',
                $wilayah->id
            )
            ->where(
                'modul_id',
                $modulId
            )
            ->where(
                'is_aktif',
                1
            )
            ->latest('id')
            ->first();



            /*
            |--------------------------------------------------------------------------
            | INTEGRASI ADHB / ADHK
            |--------------------------------------------------------------------------
            */

            $integrasi = IntegrasiPdrb::where(
                'putaran_id',
                $putaranId
            )
            ->where(
                'wilayah_id',
                $wilayah->id
            )
            ->get();



            $adhb = $integrasi
                ->where('jenis_tabel_id',1)
                ->contains(function($item){

                    return $item->status == 'selisih';

                })
                ? (object)['status'=>'selisih']
                : (object)['status'=>'sesuai'];



            $adhk = $integrasi
                ->where('jenis_tabel_id',2)
                ->contains(function($item){

                    return $item->status == 'selisih';

                })
                ? (object)['status'=>'selisih']
                : (object)['status'=>'sesuai'];



            $data[] = [

                'wilayah_id' => $wilayah->id,

                'wilayah' => $wilayah->nama,


                'status_upload' => $submission
                    ? $submission->status
                    : 'belum_upload',


                // submitted_at disimpan UTC; ditampilkan dalam WITA.
                'tanggal_upload' => $submission
                    ? $this->wita($submission->submitted_at)->format('Y-m-d')
                    : null,


                'waktu_upload' => $submission
                    ? $this->wita($submission->submitted_at)->format('H:i')
                    : null,

                'file' => $submission && $submission->files->count()
                    ? [

                        'id' => $submission->files->first()->id,

                        'nama_file' => $submission->files->first()->nama_file,

                    ]
                    : null,


                'integrasi' => [

                    'ADHB' => $adhb
                        ? $adhb->status
                        : 'belum_tersedia',


                    'ADHK' => $adhk
                        ? $adhk->status
                        : 'belum_tersedia',

                ],


                // Ekspor tersedia bila minimal 1 sel diubah pada putaran ini.
                'bisa_ekspor' => in_array($wilayah->id, $wilayahDiubah),

            ];



        }



        return response()->json([

            'putaran_id' => $putaranId,

            'modul_id' => $modulId,

            'data' => $data

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | EKSPOR EXCEL
    |--------------------------------------------------------------------------
    | Dibuat dari nilai efektif pada putaran ini (bukan berkas kiriman user),
    | sehingga selalu mengikuti perubahan terakhir dari unggah berkas
    | maupun isi tabel.
    */
    public function ekspor($putaranId, $modulId, $wilayahId, PdrbExcelExport $excel)
    {
        $modulId = (int) $modulId;

        $putaran = Putaran::findOrFail($putaranId);

        $wilayah = Wilayah::findOrFail($wilayahId);

        if (!in_array($wilayah->id, $this->wilayahDiubah($putaran->id, $modulId))) {

            return response()->json([
                'message' => 'Belum ada perubahan nilai dari wilayah ini pada putaran ini.'
            ], 422);

        }

        $path = $excel->simpanSementara(
            $excel->buat($modulId, $wilayah, $putaran, true)
        );

        return response()
            ->download($path, $excel->namaFile($modulId, $wilayah, $putaran))
            ->deleteFileAfterSend();
    }


    private function wita($waktu): Carbon
    {
        return Carbon::parse($waktu, 'UTC')->setTimezone('Asia/Makassar');
    }


    private function wilayahDiubah($putaranId, int $modulId): array
    {
        return PdrbModul::model($modulId)::where('putaran_id', $putaranId)
            ->where('tipe_data', 'source')
            ->distinct()
            ->pluck('wilayah_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

}

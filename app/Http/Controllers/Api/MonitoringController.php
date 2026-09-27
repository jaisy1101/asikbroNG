<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Wilayah;
use App\Models\IntegrasiPdrb;

class MonitoringController extends Controller
{

    public function index($putaranId, $modulId)
    {


        $wilayahList = Wilayah::whereIn(
            'jenis',
            [
                'kabupaten',
                'kota'
            ]
        )
        ->get();



        $data = [];



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


                'tanggal_upload' => $submission
                    ? date(
                        'Y-m-d',
                        strtotime($submission->submitted_at)
                    )
                    : null,


                'waktu_upload' => $submission
                    ? date(
                        'H:i',
                        strtotime($submission->submitted_at)
                    )
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

                ]

            ];



        }



        return response()->json([

            'putaran_id' => $putaranId,

            'modul_id' => $modulId,

            'data' => $data

        ]);

    }

}

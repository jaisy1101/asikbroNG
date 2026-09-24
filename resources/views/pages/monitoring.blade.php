@extends('layouts.app')

@section('content')

<div class="d-sm-flex align-items-center justify-content-between mb-4">

    <h1 class="h3 mb-0 text-gray-800">
        Monitoring
    </h1>

</div>


<!-- Filter Section -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center">


            <div class="d-flex flex-wrap">


                <div class="btn-group mr-3 mb-2" role="group">


                    <button type="button"
                            id="btnPengeluaran"
                            class="btn btn-primary"
                            onclick="gantiModul(2)">
                        Pengeluaran
                    </button>


                    <button type="button"
                            id="btnLapangan"
                            class="btn btn-outline-primary"
                            onclick="gantiModul(1)">
                        Lapangan Usaha
                    </button>


                </div>


            </div>


            <div class="d-none">

                
                <div class="mr-4">

                    <span class="font-weight-bold text-primary"
                          id="infoPutaran">

                        PUTARAN -

                    </span>

                </div>


                <div>

                    <span class="font-weight-bold text-dark"
                          id="infoRekon">

                        REKON -

                    </span>

                </div>


            </div>


        </div>

    </div>

</div>



<!-- Table -->

<div class="card shadow mb-4">


    <div class="card-header py-3">


        <h6 class="m-0 font-weight-bold text-primary">
            Status Monitoring Upload
        </h6>


    </div>



    <div class="card-body">


        <div class="table-responsive">


            <table class="table table-bordered table-hover table-monitoring">


                <thead class="thead-light">


                    <tr>

                        <th>Kabupaten/Kota</th>
                        <th>Status</th>
                        <th>Tanggal Upload</th>
                        <th>Waktu</th>
                        <th>ADHB</th>
                        <th>ADHK</th>

                    </tr>


                </thead>



                <tbody id="tableMonitoring">


                </tbody>


            </table>


        </div>


    </div>


</div>


@endsection



@section('scripts')


<script>


let putaranId = null;

let modulId = 2;



axios.get('/api/rekonsiliasi/status')

.then(response => {


    putaranId =
        response.data.putaran_aktif.id;



    document.getElementById('infoPutaran').innerHTML =
        'PUTARAN ' +
        response.data.putaran_aktif.nomor;



    document.getElementById('infoRekon').innerHTML =
        'REKON ' +
        response.data.rekonsiliasi.tahun +
        ' Q' +
        response.data.rekonsiliasi.triwulan;



    // default awal Pengeluaran
    loadMonitoring();



})

.catch(error => {

    console.error(error);

});



function gantiModul(id)
{

    modulId = id;



    if(id == 1){


        document.getElementById('btnLapangan')
            .className =
            'btn btn-primary';


        document.getElementById('btnPengeluaran')
            .className =
            'btn btn-outline-primary';


    }
    else{


        document.getElementById('btnPengeluaran')
            .className =
            'btn btn-primary';


        document.getElementById('btnLapangan')
            .className =
            'btn btn-outline-primary';


    }



    loadMonitoring();

}



function loadMonitoring()
{


    console.log(
        'API:',
        `/api/monitoring/${putaranId}/${modulId}`
    );


    axios.get(
        `/api/monitoring/${putaranId}/${modulId}`
    )


    .then(response => {


        console.log(response.data);



        let data =
            response.data.data;



        let html = '';



        data.forEach(item => {


            html += `

            <tr>

                <td>
                    ${item.wilayah}
                </td>


                <td>
                    ${badgeUpload(item.status_upload)}
                </td>


                <td>
                    ${item.tanggal_upload ?? '-'}
                </td>


                <td>
                    ${item.waktu_upload ?? '-'}
                </td>


                <td>
                    ${badgeIntegrasi(item.integrasi.ADHB)}
                </td>


                <td>
                     ${badgeIntegrasi(item.integrasi.ADHK)}
                </td>


            </tr>

            `;


        });



        document.getElementById(
            'tableMonitoring'
        ).innerHTML = html;



    })

    .catch(error=>{

        console.error(error);

    });


}


function badgeIntegrasi(status)
{


    if(status == 'sesuai'){


        return `

        <span class="badge-monitoring badge-success">

            <i class="fas fa-check mr-1"></i>

            Sesuai

        </span>

        `;


    }



    if(status == 'selisih'){


        return `

        <span class="badge-monitoring badge-danger">

            <i class="fas fa-times mr-1"></i>

            Selisih

        </span>

        `;


    }



    return `

    <span class="badge-monitoring badge-secondary">

        <i class="fas fa-minus-circle mr-1"></i>

        Belum Ada

    </span>

    `;


}

function badgeUpload(status)
{


    if(status == 'belum_upload'){

        return `
        <span class="badge-monitoring badge-warning">

            <i class="fas fa-clock"></i>

            Belum Upload

        </span>
        `;

    }



    if(status == 'perlu_perbaikan'){

        return `
        <span class="badge-monitoring badge-danger">

            <i class="fas fa-exclamation-circle"></i>

            Revisi

        </span>
        `;

    }



    if(
        status == 'valid' ||
        status == 'disetujui'
    ){

        return `
        <span class="badge-monitoring badge-success">

            <i class="fas fa-check-circle"></i>

            Selesai

        </span>
        `;

    }



    return `
    <span class="badge-monitoring badge-primary">

        <i class="fas fa-upload"></i>

        ${status.replace('_',' ')}

    </span>
    `;


}

</script>


@endsection
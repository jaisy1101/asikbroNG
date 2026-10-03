@extends('layouts.app')

@section('content')

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">

    <h1 class="h3 mb-0 text-gray-800">
        Integrasi
    </h1>

</div>

<!-- Filter Wilayah -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="row">

            <div class="col-md-4">

                <label class="font-weight-bold">
                    Pilih Wilayah
                </label>

                <select class="form-control" id="wilayah_id">

                </select>

            </div>

        </div>

    </div>

</div>

<!-- Summary Cards -->
<div class="row mb-4">

    <!-- ADHB -->
    <div class="col-md-6 mb-3">

        <div id ="cardADHB"
             class="card shadow border-left-danger h-100"
             style="cursor: pointer;"
             data-toggle="collapse"
             data-target="#detailADHB">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="font-weight-bold title-text mb-1"
                             style="font-size: 22px;">

                            ADHB

                        </div>

                        <div class="text-gray-800 status-text"
                            style="font-size: 16px;">

                            Ada Selisih

                        </div>

                    </div>

                    <div>

                        <i class="fas fa-exclamation-triangle text-danger"
                           style="font-size: 32px;">
                        </i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ADHK -->
    <div class="col-md-6 mb-3">

        <div id ="cardADHK"
             class="card shadow border-left-success h-100"
             style="cursor: pointer;"
             data-toggle="collapse"
             data-target="#detailADHK">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="font-weight-bold title-text mb-1"
                             style="font-size: 22px;">

                            ADHK

                        </div>

                        <div class="text-gray-800 status-text"
                             style="font-size: 16px;">

                            Tidak Ada Selisih

                        </div>

                    </div>

                    <div>

                        <i class="fas fa-check-circle text-success"
                           style="font-size: 32px;">
                        </i>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- Detail ADHB -->
<div class="collapse mb-4" id="detailADHB">

    <div class="card shadow">

        <div class="card-header py-3">

            <h6 id="titleDetailADHB"
                class="m-0 font-weight-bold text-danger">
                Detail Integrasi ADHB
            </h6>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-monitoring">

                    <thead>

                        <tr>

                            <th>Triwulan</th>
                            <th>Pengeluaran</th>
                            <th>Lapangan Usaha</th>
                            <th>Selisih</th>
                            <th>Status</th>

                        </tr>

                    </thead>
                        <tbody id="tableADHB">

                        </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- Detail ADHK -->
<div class="collapse mb-4" id="detailADHK">

    <div class="card shadow">

        <div class="card-header py-3">

            <h6 id="titleDetailADHK"
                class="m-0 font-weight-bold text-success">
                Detail Integrasi ADHK
            </h6>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-monitoring">

                    <thead>

                        <tr>

                            <th>Triwulan</th>
                            <th>Pengeluaran</th>
                            <th>Lapangan Usaha</th>
                            <th>Selisih</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                        <tbody id="tableADHK">

                        </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection

@section('scripts')

<script>

let putaranId = null;

function ambilWilayah(){

    axios.get('/api/wilayah')
    .then(response => {


        console.log("HASIL WILAYAH", response.data);


        let data = response.data.data;

        let select = document.getElementById('wilayah_id');


        console.log("SELECT", select);


        select.innerHTML = '';


        data.forEach(item => {

            select.innerHTML += `

                <option value="${item.id}">
                    ${item.nama}
                </option>

            `;

        });



        if(data.length === 1){

            select.disabled = true;

        }


        loadIntegrasi(select.value);


    })
    .catch(error => {

        console.error("WILAYAH ERROR", error);

    });

}

axios.get('/api/rekonsiliasi/status')

.then(response => {


    putaranId =
        response.data.putaran_terakhir.id;


    ambilWilayah();


})

.catch(error => {

    console.error(
        error
    );

});

function formatAngka(nilai){

    if(nilai === null || nilai === undefined || nilai === ''){
        return '-';
    }


    if(isNaN(nilai)){
        return nilai;
    }


    return Number(nilai).toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

}

function loadIntegrasi(wilayahId)
{

    if(!putaranId){

        return;

    }   
    console.log(
        `/api/integrasi/${putaranId}/${wilayahId}`
    );

    axios.get(`/api/integrasi/${putaranId}/${wilayahId}`)

    .then(response => {

        let result = response.data;


        let data = result.table;


        let adhb = data.filter(item =>
            item.jenis_tabel.kode === 'ADHB'
        );


        let adhk = data.filter(item =>
            item.jenis_tabel.kode === 'ADHK'
        );


        updateSummary(
            'ADHB',
            adhb
        );


        updateSummary(
            'ADHK',
            adhk
        );


        renderDetail(
            'ADHB',
            adhb
        );


        renderDetail(
            'ADHK',
            adhk
        );


    });


}

function updateSummary(kode,data)
{

    let card =
        document.getElementById(
            kode === 'ADHB'
            ? 'cardADHB'
            : 'cardADHK'
        );


    let status =
        card.querySelector('.status-text');


    let title =
        card.querySelector('.title-text');


    let icon =
        card.querySelector('i');



    let detailTitle =
        document.getElementById(
            kode === 'ADHB'
            ? 'titleDetailADHB'
            : 'titleDetailADHK'
        );



    let adaSelisih =
        data.some(item =>
            item.status === 'selisih'
        );



    // reset warna
    card.classList.remove(
        'border-left-danger',
        'border-left-success'
    );


    title.classList.remove(
        'text-danger',
        'text-success'
    );


    if(detailTitle){

        detailTitle.classList.remove(
            'text-danger',
            'text-success'
        );

    }



    if(adaSelisih){


        status.innerHTML =
            'Ada Selisih';


        icon.className =
            'fas fa-exclamation-triangle text-danger';



        card.classList.add(
            'border-left-danger'
        );


        title.classList.add(
            'text-danger'
        );


        if(detailTitle){

            detailTitle.classList.add(
                'text-danger'
            );

        }



    }
    else{


        status.innerHTML =
            'Tidak Ada Selisih';


        icon.className =
            'fas fa-check-circle text-success';



        card.classList.add(
            'border-left-success'
        );


        title.classList.add(
            'text-success'
        );


        if(detailTitle){

            detailTitle.classList.add(
                'text-success'
            );

        }


    }

}


function renderDetail(kode,data)
{

    data.sort((a,b)=>{

        let periodeA =
            (a.periode.tahun * 10) + a.periode.triwulan;


        let periodeB =
            (b.periode.tahun * 10) + b.periode.triwulan;


        return periodeA - periodeB;

    });


    let tbody =
        document.getElementById(
            kode === 'ADHB'
            ? 'tableADHB'
            : 'tableADHK'
        );



    let html = '';



    data.forEach(item=>{


        let statusHtml = '';



        if(item.status === 'sesuai'){


            statusHtml = `

            <span class="badge-monitoring badge-success">

                <i class="fas fa-check"></i>

                Sesuai

            </span>

            `;


        }
        else{


            statusHtml = `

            <span class="badge-monitoring badge-danger">

                <i class="fas fa-times"></i>

                Selisih

            </span>

            `;


        }



        html += `

        <tr>

            <td>
                ${item.periode.tahun}
                Q${item.periode.triwulan}
            </td>

            <td>
                ${formatAngka(item.total_pengeluaran)}
            </td>


            <td>
                ${formatAngka(item.total_lapus)}
            </td>


            <td>
                ${formatAngka(item.selisih)}
            </td>


            <td>
                ${statusHtml}
            </td>


        </tr>

        `;


    });



    tbody.innerHTML = html;


}

document
.getElementById('wilayah_id')
.addEventListener(
    'change',
    function(){

        loadIntegrasi(this.value);

    }
);



</script>

@endsection
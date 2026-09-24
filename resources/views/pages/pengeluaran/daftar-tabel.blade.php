@extends('layouts.app')

@section('content')

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">

    <ol class="breadcrumb bg-white shadow-sm">

        <li class="breadcrumb-item">
            <a href="/">Beranda</a>
        </li>

        <li class="breadcrumb-item">
            Pengeluaran
        </li>

        <li class="breadcrumb-item active" aria-current="page">
            Daftar Tabel
        </li>

    </ol>

</nav>


<!-- Filter Section 1 -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <!-- Kiri -->
            <div class="d-flex flex-wrap align-items-center">
                {{--  
                <!-- Toggle ADHB / ADHK -->
                <div class="btn-group mr-3 mb-2" role="group">

                    <button type="button"
                            class="btn btn-primary"
                            data-id="1">
                        ADHB
                    </button>

                    <button type="button"
                            class="btn btn-outline-primary"
                            data-id="2">
                        ADHK
                    </button>

                </div>
                --}}


                <!-- Dropdown Wilayah -->
                <div class="mr-3 mb-2">
                    
                    <select class="form-control" id="wilayah_id">

                        <option value="1" selected>
                            Sulawesi Selatan
                        </option>

                        <option value="2">
                            Kepulauan Selayar
                        </option>

                        <option value="3">
                            Bulukumba
                        </option>

                        <option value="4">
                            Bantaeng
                        </option>

                        <option value="5">
                            Jeneponto
                        </option>

                        <option value="6">
                            Takalar
                        </option>

                        <option value="7">
                            Gowa
                        </option>

                        <option value="8">
                            Sinjai
                        </option>

                        <option value="9">
                            Maros
                        </option>

                        <option value="10">
                            Pangkajene dan Kepulauan
                        </option>

                        <option value="11">
                            Barru
                        </option>

                        <option value="12">
                            Bone
                        </option>

                        <option value="13">
                            Soppeng
                        </option>

                        <option value="14">
                            Wajo
                        </option>

                        <option value="15">
                            Sidenreng Rappang
                        </option>

                        <option value="16">
                            Pinrang
                        </option>

                        <option value="17">
                            Enrekang
                        </option>

                        <option value="18">
                            Luwu
                        </option>

                        <option value="19">
                            Tana Toraja
                        </option>

                        <option value="20">
                            Luwu Utara
                        </option>

                        <option value="21">
                            Luwu Timur
                        </option>

                        <option value="22">
                            Toraja Utara
                        </option>

                        <option value="23">
                            Makassar
                        </option>

                        <option value="24">
                            Parepare
                        </option>

                        <option value="25">
                            Palopo
                        </option>

                    </select>

                </div>

                {{--  
                <!-- Dropdown Tahun -->
                <div class="mr-3 mb-2">

                    <select class="form-control" id="filter-tahun">
                    </select>

                </div>


                <!-- Dropdown Triwulan -->
                <div class="mr-3 mb-2">

                    <select class="form-control" id="filter-triwulan">
                    </select>

                </div>


                <!-- Dropdown Putaran -->
                <div class="mb-2">

                    <select class="form-control" id="filter-putaran">
                    </select>

                </div>
                --}}

            </div>


            <!-- Kanan -->
            <div class="mt-2 mt-md-0">

                <button class="btn btn-success">

                    <i class="fas fa-download mr-2"></i>

                    Unduh Tabel

                </button>

            </div>

        </div>

    </div>

</div>


<!-- Filter Section 2 -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="d-flex flex-wrap">

                <button class="btn btn-primary mr-2 mb-2 jenis-tabel"
                        data-id="1">
                    ADHB
                </button>

                <button class="btn btn-outline-primary mr-2 mb-2 jenis-tabel"
                        data-id="2">
                    ADHK
                </button>


                <button class="btn btn-outline-primary mr-2 mb-2 jenis-tabel"
                        data-id="3">
                    Distribusi
                </button>

                <button class="btn btn-outline-primary mr-2 mb-2 jenis-tabel"
                        data-id="7">
                    Indeks Implisit
                </button>

                <button class="btn btn-outline-primary mr-2 mb-2 jenis-tabel"
                        data-id="8">
                    Laju Implisit QtoQ
                </button>

                <button class="btn btn-outline-primary mr-2 mb-2 jenis-tabel"
                        data-id="9">
                    Laju Implisit YtoY
                </button>

                <button class="btn btn-outline-primary mr-2 mb-2 jenis-tabel"
                        data-id="5">
                    YtoY
                </button>

                <button class="btn btn-outline-primary mr-2 mb-2 jenis-tabel"
                        data-id="4">
                    QtoQ
                </button>

                <button class="btn btn-outline-primary mr-2 mb-2 jenis-tabel"
                        data-id="6">
                    CtoC
                </button>
            

        </div>

    </div>

</div>


<!-- Tabel -->
<div class="card shadow">

    <div class="card-header py-3">

        <h6 class="m-0 font-weight-bold text-primary">
            Daftar Tabel
        </h6>

    </div>


    <div class="card-body">

        <div class="table-responsive">

            <table  class="table table-bordered table-hover table-data-large">
                
                <thead class="thead-light" id="header-tabel">

                </thead>


                <tbody id="tabel-pdrb">

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection

@section('scripts')

<script>

let jenisTabelId = 1;
let tahunAkhir = null;

// ===============================
// AMBIL STATUS REKONSILIASI
// ===============================

function ambilStatusRekonsiliasi(){


    axios.get('/api/rekonsiliasi/status')

    .then(response => {


        let data = response.data;


        console.log('STATUS REKONSILIASI', data);


        if(data.rekonsiliasi){


            tahunAkhir = Number(
                data.rekonsiliasi.tahun
            );


            buatHeaderTabel(tahunAkhir);

            ambilTabelPdrb(tahunAkhir);

        }


    })


    .catch(error => {

        console.error(error);

    });


}

// ===============================
// PILIH JENIS TABEL
// ===============================

document.querySelectorAll('.jenis-tabel')
.forEach(button => {


    button.addEventListener('click', function(){


        jenisTabelId = this.dataset.id;


        document.querySelectorAll('.jenis-tabel')
        .forEach(btn => {


            btn.classList.remove('btn-primary');

            btn.classList.add('btn-outline-primary');


        });



        this.classList.remove('btn-outline-primary');

        this.classList.add('btn-primary');



        ambilTabelPdrb(tahunAkhir);


    });


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


function buatHeaderTabel(tahunAkhir){


    let html = `

        <tr>

            <th rowspan="2"
                class="kolom-kategori">
                Kategori
            </th>

    `;


    for(let tahun = 2010; tahun <= tahunAkhir; tahun++){

        html += `

            <th colspan="5">
                ${tahun}
            </th>

        `;

    }


    html += `

        </tr>

        <tr>

    `;


    for(let tahun = 2010; tahun <= tahunAkhir; tahun++){

        html += `

            <th>I</th>
            <th>II</th>
            <th>III</th>
            <th>IV</th>
            <th>Total</th>

        `;

    }


    html += `

        </tr>

    `;


    document.getElementById('header-tabel')
        .innerHTML = html;


}

// ===============================
// AMBIL DATA PDRB
// ===============================

function ambilTabelPdrb(tahunAkhir){


    // Sulawesi Selatan
    let wilayah_id = document.getElementById('wilayah_id').value;


    axios.get(
        `/api/pdrb/pengeluaran/${wilayah_id}/${jenisTabelId}`
    )


    .then(response => {


        let data = response.data.table;

        let html = "";


        data.forEach(item => {


            html += `

            <tr class="level-${item.level}">

                <td class="kolom-kategori"
                    style="
                    padding-left:${(item.level - 1) * 30}px;
                    font-weight:${item.level == 1 ? 'bold' : 'normal'};
                ">

                    ${item.kode ?? ''}
                    ${item.kategori}

                </td>

            `;


            let periode = [];


            for(let tahun = 2010; tahun <= tahunAkhir; tahun++){

                periode.push(`${tahun} Q1`);
                periode.push(`${tahun} Q2`);
                periode.push(`${tahun} Q3`);
                periode.push(`${tahun} Q4`);
                periode.push(`TOTAL_${tahun}`);

            }


            periode.forEach(key => {


                let nilai = '-';


                if(key.startsWith('TOTAL_')){


                    let tahun = key.replace('TOTAL_', '');


                    let q1 = Number(item[`${tahun} Q1`] ?? 0);
                    let q2 = Number(item[`${tahun} Q2`] ?? 0);
                    let q3 = Number(item[`${tahun} Q3`] ?? 0);
                    let q4 = Number(item[`${tahun} Q4`] ?? 0);


                    if(q1 || q2 || q3 || q4){

                        nilai = formatAngka(
                            q1 + q2 + q3 + q4
                        );

                    }


                }
                else{


                    nilai = formatAngka(
                        item[key]
                    );


                }


                html += `

                    <td>
                        ${nilai}
                    </td>

                `;


            });


            html += `</tr>`;


        });


        document.getElementById('tabel-pdrb')
            .innerHTML = html;


        let scrollTable =
            document.querySelector('.table-responsive');


        scrollTable.scrollLeft =
            scrollTable.scrollWidth;


    })


    .catch(error => {

        console.error(error);

    });


}



// ===============================
// GANTI WILAYAH
// ===============================

document.getElementById('wilayah_id')
.addEventListener('change', function(){


    if(tahunAkhir){

        ambilTabelPdrb(tahunAkhir);

    }


});


// ===============================
// LOAD AWAL
// ===============================

console.log('HALAMAN DAFTAR TABEL AKTIF');

ambilStatusRekonsiliasi();

</script>

@endsection
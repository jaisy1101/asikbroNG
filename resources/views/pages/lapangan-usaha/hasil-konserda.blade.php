@extends('layouts.app')

@section('content')

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">

    <ol class="breadcrumb bg-white shadow-sm">

        <li class="breadcrumb-item">
            <a href="/">Beranda</a>
        </li>

        <li class="breadcrumb-item">
            Lapangan Usaha
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
 
                <!-- Toggle ADHB / ADHK -->
                <div class="btn-group mr-3 mb-2" role="group">

                    <button type="button"
                            class="btn btn-primary btn-jenis-tabel"
                            data-id="1">
                        ADHB
                    </button>

                    <button type="button"
                            class="btn btn-outline-primary btn-jenis-tabel"
                            data-id="2">
                        ADHK
                    </button>

                </div>

                <!-- Dropdown Tahun -->
                <div class="mr-3 mb-2">

                    <select class="form-control" id="filter-tahun">

                        <option selected>
                            2026
                        </option>

                        <option>2025</option>
                        <option>2024</option>
                        <option>2023</option>
                        <option>2022</option>
                        <option>2021</option>
                        <option>2020</option>
                        <option>2019</option>
                        <option>2018</option>
                        <option>2017</option>
                        <option>2016</option>
                        <option>2015</option>
                        <option>2014</option>
                        <option>2013</option>

                    </select>

                </div>


                <!-- Dropdown Triwulan -->
                <div class="mr-3 mb-2">

                    <select class="form-control" id="filter-triwulan">

                        <option selected>
                            Q2
                        </option>

                        <option>Q1</option>
                        <option>Q2</option>
                        <option>Q3</option>
                        <option>Q4</option>

                    </select>

                </div>


                <!-- Dropdown Putaran -->
                <div class="mb-2">

                    <select class="form-control" id="filter-putaran">

                        <option selected>
                            Putaran 0
                        </option>

                        <option>Putaran 1</option>
                        <option>Putaran 2</option>
                        <option>Putaran 3</option>
                        <option>Putaran 4</option>

                    </select>

                </div>

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
{{--  
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
--}}

<!-- Tabel -->
<div class="card shadow">

    <div class="card-header py-3">

        <h6 class="m-0 font-weight-bold text-primary">

            Daftar Tabel

        </h6>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered table-hover table-data-large">

                <thead class="thead-light" id="header-konserda">

                </thead>

                <tbody id="tabel-konserda">

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection

@section('scripts')

<script>

let putaranId = null;
let jenisTabelId = 1;

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

axios.get('/api/rekonsiliasi/status')
.then(response => {


    putaranId = response.data.putaran_terakhir.id;


    loadKonserda();


})
.catch(error => {

    console.error(error);

});

function loadKonserda(){

axios.get(
    `/api/konserda/lapangan-usaha/${putaranId}/${jenisTabelId}`
)

.then(response => {


    console.log(response.data);

    let data = response.data.data;

    let periode = [
        ...new Map(
            data.map(item => [
                item.periode_id,
                item.periode
            ])
        ).values()
    ]
    .sort((a,b)=>{

        if(a.tahun != b.tahun){

            return a.tahun - b.tahun;

        }

        return a.triwulan - b.triwulan;

    });

    // ===============================
    // AMBIL DAFTAR KAB/KOTA
    // ===============================

    let kabkotaMap = new Map();

    data.forEach(item => {

        item.detail?.forEach(detail => {

            if (detail.wilayah) {

                kabkotaMap.set(
                    detail.wilayah.id,
                    detail.wilayah
                );

            }

        });

    });


    let kabkotaList = Array.from(
        kabkotaMap.values()
    );


    // ===============================
    // GROUP TAHUN
    // ===============================

    let tahunGroup = {};

    periode.forEach(p => {

        if (!tahunGroup[p.tahun]) {

            tahunGroup[p.tahun] = 0;

        }

        tahunGroup[p.tahun]++;

    });


    // ===============================
    // HEADER BARIS 1
    // ===============================

    let header = `

    <tr>

        <th rowspan="3" class="kolom-kategori">
            Kategori
        </th>

        <th colspan="${periode.length}">
            Diskrepansi
        </th>

        <th colspan="${periode.length}">
            Selisih
        </th>

        <th colspan="${periode.length}">
            Provinsi
        </th>

        <th colspan="${periode.length}">
            Kab/Kota
        </th>

    `;


    // SETIAP KAB/KOTA JADI HEADER SENDIRI

    kabkotaList.forEach(wilayah => {

        header += `

            <th colspan="${periode.length}">
                ${wilayah.nama}
            </th>

        `;

    });


    header += `

    </tr>


    <tr>

    `;


    // ===============================
    // HEADER BARIS 2 : TAHUN
    // ===============================


    // Diskrepansi
    Object.keys(tahunGroup).forEach(tahun => {

        header += `

            <th colspan="${tahunGroup[tahun]}">
                ${tahun}
            </th>

        `;

    });


    // Selisih
    Object.keys(tahunGroup).forEach(tahun => {

        header += `

            <th colspan="${tahunGroup[tahun]}">
                ${tahun}
            </th>

        `;

    });


    // Provinsi
    Object.keys(tahunGroup).forEach(tahun => {

        header += `

            <th colspan="${tahunGroup[tahun]}">
                ${tahun}
            </th>

        `;

    });

    
    // Kab/Kota
    Object.keys(tahunGroup).forEach(tahun => {

        header += `

            <th colspan="${tahunGroup[tahun]}">
                ${tahun}
            </th>

        `;

    });

    // SETIAP KAB/KOTA
    kabkotaList.forEach(() => {

        Object.keys(tahunGroup).forEach(tahun => {

            header += `

                <th colspan="${tahunGroup[tahun]}">
                    ${tahun}
                </th>

            `;

        });

    });


    header += `

    </tr>


    <tr>

    `;


    // ===============================
    // HEADER BARIS 3 : TRIWULAN
    // ===============================


    // Diskrepansi
    periode.forEach(p => {

        header += `
            <th>Q${p.triwulan}</th>
        `;

    });


    // Selisih
    periode.forEach(p => {

        header += `
            <th>Q${p.triwulan}</th>
        `;

    });


    // Provinsi
    periode.forEach(p => {

        header += `
            <th>Q${p.triwulan}</th>
        `;

    });

    // Kab/Kota
    periode.forEach(p => {

        header += `
            <th>Q${p.triwulan}</th>
        `;

    });

    // SETIAP KAB/KOTA
    kabkotaList.forEach(() => {

        periode.forEach(p => {

            header += `
                <th>Q${p.triwulan}</th>
            `;

        });

    });


    header += `

    </tr>

    `;


    // ===============================
    // MASUKKAN HEADER
    // ===============================

    document.getElementById('header-konserda')
    .innerHTML = header;


    // ===============================
    // GROUP DATA PER KATEGORI
    // ===============================

    let kategoriGroup = {};

    data.forEach(item => {

        let key = item.kategori_id ?? 'total';


        if (!kategoriGroup[key]) {

            kategoriGroup[key] = [];

        }


        kategoriGroup[key].push(item);

    });



    let html = "";



    // ===============================
    // LOOP KATEGORI
    // ===============================

    Object.values(kategoriGroup).forEach(items => {


        let kategori = items[0].kategori;


        html += `

        <tr class="level-${kategori?.level ?? 0}">


            <td class="kolom-kategori"
                style="
                    padding-left:${(kategori?.level ?? 0) * 30}px;
                    font-weight:${kategori?.level == 1 || !kategori ? 'bold' : 'normal'};
                ">
                ${kategori?.kode ?? ''}
                ${kategori?.nama ?? 'TOTAL'}
            </td>

        `;



        // ===============================
        // DISKREPANSI
        // ===============================

        periode.forEach(p => {


            let item = items.find(i => 
                i.periode_id == p.id
            );


            html += `

            <td>
                 
                    ${formatAngka(item 
                        ? item.diskrepansi_persen 
                        : '-'
                    )}
            </td>

            `;


        });




        // ===============================
        // SELISIH
        // ===============================

        periode.forEach(p => {


            let item = items.find(i => 
                i.periode_id == p.id
            );


            html += `

            <td>
                ${formatAngka(item 
                    ? item.selisih 
                    : '-'
                )}
            </td>

            `;


        });





        // ===============================
        // PROVINSI
        // ===============================

        periode.forEach(p => {


            let item = items.find(i => 
                i.periode_id == p.id
            );


            html += `

            <td>
                ${formatAngka(item 
                    ? item.nilai_provinsi
                    : '-'
                )}
            </td>

            `;


        });





        // ===============================
        // KAB/KOTA TOTAL
        // ===============================

        periode.forEach(p => {


            let item = items.find(i => 
                i.periode_id == p.id
            );


            html += `

            <td>
                ${formatAngka(item 
                    ? item.nilai_agregasi_kabkota
                    : '-'
                )}
            </td>

            `;


        });





        // ===============================
        // DETAIL KAB/KOTA
        // ===============================

        kabkotaList.forEach(wilayah => {


            periode.forEach(p => {


                let item = items.find(i => 
                    i.periode_id == p.id
                );


                let detail = item?.detail.find(d =>
                    d.wilayah_id == wilayah.id
                );



                html += `

                <td>
                    ${detail
                        ? detail.nilai
                        : '-'
                    }
                </td>

                `;


            });


        });



        html += `

        </tr>

        `;



    });



    document.getElementById('tabel-konserda')
    .innerHTML = html;


})

.catch(error => {

    console.error(error);

});
}

document.querySelectorAll('.btn-jenis-tabel')
.forEach(button => {


    button.addEventListener('click', function(){


        jenisTabelId = this.dataset.id;



        document.querySelectorAll('.btn-jenis-tabel')
        .forEach(btn => {

            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');

        });



        this.classList.remove('btn-outline-primary');
        this.classList.add('btn-primary');



        loadKonserda();


    });


});

loadKonserda();

</script>

@endsection
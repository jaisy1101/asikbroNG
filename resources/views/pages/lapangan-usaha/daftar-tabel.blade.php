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


                <!-- Dropdown Tahun -->
                <div class="mr-3 mb-2">

                    <select class="form-control">

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

                    <select class="form-control">

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

                    <select class="form-control">

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


<!-- Filter Section 2 -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="d-flex flex-wrap">

            <button class="btn btn-primary mr-2 mb-2 jenis-tabel"
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

            <table class="table table-bordered table-hover table-data-large">

                <thead class="thead-light">

                    <!-- Baris Tahun -->
                    <tr>

                        <th rowspan="2">
                            Kategori
                        </th>

                        @for($tahun = 2010; $tahun <= 2026; $tahun++)

                            <th colspan="5">
                                {{ $tahun }}
                            </th>

                        @endfor

                    </tr>


                    <!-- Baris Triwulan -->
                    <tr>

                        @for($tahun = 2010; $tahun <= 2026; $tahun++)

                            <th>I</th>
                            <th>II</th>
                            <th>III</th>
                            <th>IV</th>
                            <th>Total</th>

                        @endfor

                    </tr>

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


// pilih jenis tabel
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


        ambilTabelPdrb();


    });


});



// ambil data tabel

console.log('HALAMAN DAFTAR TABEL AKTIF');

function ambilTabelPdrb(){


    let wilayah_id = document.getElementById('wilayah_id').value;


    axios.get(
        `/api/pdrb/lapangan-usaha/${wilayah_id}/${jenisTabelId}`
    )
    .then(response => {

        let data = response.data.table;

        let html = "";


        data.forEach(item => {

            html += `<tr>`;

            // kolom kategori
            html += `
                <td>
                    ${item.kategori}
                </td>
            `;


            // semua kolom periode
            Object.keys(item).forEach(key => {

                if(key !== 'kategori') {

                    html += `
                        <td>
                            ${item[key]}
                        </td>
                    `;

                }

            });


            html += `</tr>`;

        });


        document.getElementById('tabel-pdrb').innerHTML = html;


    })
    .catch(error => {


        console.error(error);


    });


}


function buatKolomNilai(item){


    let html = "";


    for(let i = 0; i < 85; i++){

        html += `

        <td>
            ${item.nilai ?? '-'}
        </td>

        `;

    }


    return html;

}

// ketika wilayah diganti
document.getElementById('wilayah_id')
.addEventListener('change', function(){

    ambilTabelPdrb();

});

// load awal
ambilTabelPdrb();


</script>

@endsection
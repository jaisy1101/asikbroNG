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
                            class="btn btn-primary">
                        ADHB
                    </button>

                    <button type="button"
                            class="btn btn-outline-primary">
                        ADHK
                    </button>

                </div>

                <!-- Dropdown Wilayah -->
                <div class="mr-3 mb-2">

                    <select class="form-control">

                        <option selected>
                            Sulawesi Selatan
                        </option>

                        <option>
                            Kota Makassar
                        </option>

                        <option>
                            Kabupaten Gowa
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

            <button class="btn btn-primary mr-2 mb-2">
                Struktur Dalam
            </button>

            <button class="btn btn-primary mr-2 mb-2">
                Struktur Antar
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                Indeks Implisit
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                Laju Implisit
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                Sumber Pertumbuhan
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                YtoY
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
                QtoQ
            </button>

            <button class="btn btn-outline-primary mr-2 mb-2">
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

            <table class="table table-bordered table-hover">

                <thead class="thead-light">

                <tr>

                    <th>Kategori</th>
                    <th>Diskrepansi</th>
                    <th>Selisih</th>
                    <th>Provinsi</th>
                    <th>Kab/Kota</th>

                </tr>

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


let putaranId = 27; 
// sementara tes dulu


axios.get(`/api/konserda/${putaranId}`)
.then(response => {


    console.log(response.data);



    let data = response.data.data;


    let html = "";



    data.forEach(item => {


        html += `

        <tr>

            <td>
                ${item.kategori}
            </td>

            <td>
                ${item.diskrepansi ?? '-'}
            </td>

            <td>
                ${item.selisih ?? '-'}
            </td>

            <td>
                ${item.wilayah_parent?.nama ?? '-'}
            </td>

            <td>
                ${item.wilayah?.nama ?? '-'}
            </td>

        </tr>

        `;


    });



    document.getElementById('tabel-konserda')
    .innerHTML = html;



})
.catch(error => {

    console.error(error);

});


</script>

@endsection
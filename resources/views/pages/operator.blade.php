@extends('layouts.app')

@section('content')

<!-- Header -->
<div class="mb-4">

    <h1 class="h4 font-weight-bold text-gray-800 mb-4">
        Operator
    </h1>

</div>

<!-- SECTION -->
<div class="mb-5">

    <div class="row align-items-stretch">
        <!-- ========================================= -->
        <!-- KIRI -->
        <!-- ========================================= -->
        <div class="col-lg-6 mb-4 d-flex">

            <div class="card shadow-sm border-0 w-100"
                style="
                    border-radius: 25px;
                ">

                <div class="card-body p-4 d-flex flex-column">

                    <!-- Isi -->
                    <div class="flex-grow-1">

                        <!-- Header -->
                        <div class="d-flex align-items-center mb-4">

                            <div class="mr-3">

                                <div class="rounded-circle d-flex justify-content-center align-items-center"
                                    style="
                                        width: 40px;
                                        height: 40px;
                                        border: 2px solid #36b9cc;
                                        color: #36b9cc;
                                        font-size: 20px;
                                        font-weight: bold;
                                    ">

                                    !

                                </div>

                            </div>

                            <h5 class="font-weight-bold text-gray-800 mb-0">

                                Buka Putaran Baru

                            </h5>

                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-4"
                            style="
                                font-size: 15px;
                                line-height: 1.8;
                                color: #5a5c69;
                            ">

                            Setelah putaran atau triwulan dibuka, 
                            periode sebelumnya tidak dapat dibuka kembali. 
                            Putaran yang sedang berlangsung harus ditutup 
                            terlebih dahulu sebelum membuka putaran baru.
                        </div>

                    </div>

                    <!-- Tombol -->
                    <button class="btn btn-success btn-block py-2 mt-4"
                            data-toggle="modal"
                            data-target="#modalBukaPutaran"
                            style="
                                border-radius: 18px;
                                font-size: 16px;
                                font-weight: 600;
                            ">

                        Buka Putaran

                    </button>

                </div>

            </div>

        </div>

        <!-- ========================================= -->
        <!-- KANAN -->
        <!-- ========================================= -->
        <div class="col-lg-6 mb-4 d-flex">

            <div class="card shadow-sm border-0 w-100"
                style="
                    border-radius: 25px;
                ">

                <div class="card-body p-4 d-flex flex-column">

                    <!-- Isi -->
                    <div class="flex-grow-1">

                        <!-- Header -->
                        <div class="d-flex align-items-center mb-4">

                            <div class="mr-3">

                                <div class="rounded-circle d-flex justify-content-center align-items-center"
                                    style="
                                        width: 40px;
                                        height: 40px;
                                        border: 2px solid #1cc88a;
                                        color: #1cc88a;
                                        font-size: 20px;
                                        font-weight: bold;
                                    ">

                                    ✓

                                </div>

                            </div>

                            <h5 class="font-weight-bold text-gray-800 mb-0">

                                Putaran Berlangsung

                            </h5>

                        </div>

                        <!-- Informasi -->
                        <div class="row text-center">

                            <div class="col-4">

                                <small class="text-muted d-block mb-2">
                                    TAHUN
                                </small>

                                <h3 id="statusTahun"
                                    class="font-weight-bold text-dark mb-0">
                                    -
                                </h3>

                            </div>

                            <div class="col-4">

                                <small class="text-muted d-block mb-2">
                                    TRIWULAN
                                </small>

                                <h3 id="statusTriwulan"
                                    class="font-weight-bold text-dark mb-0">
                                    -
                                </h3>

                            </div>

                            <div class="col-4">

                                <small class="text-muted d-block mb-2">
                                    PUTARAN
                                </small>

                                <h3 id="statusPutaran"
                                    class="font-weight-bold text-dark mb-0">
                                    -
                                </h3>

                            </div>

                        </div>

                        <div class="text-center mt-4">

                            <span id="statusBadge"
                                class="badge badge-success px-4 py-2"
                                style="
                                    border-radius: 20px;
                                    font-size: 14px;
                                ">

                                -

                            </span>

                        </div>

                    </div>

                    <!-- Tombol -->
                    <button class="btn btn-danger btn-block py-2 mt-4"
                            data-toggle="modal"
                            data-target="#modalTutupPutaran"
                            style="
                                border-radius: 18px;
                                font-size: 16px;
                                font-weight: 600;
                            ">

                        Tutup Putaran

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- ========================================= -->
<!-- PENGUMUMAN -->
<!-- ========================================= -->
<div>

    <div class="d-flex align-items-center mb-3">

        <div class="mr-3">

            <div class="rounded-circle d-flex justify-content-center align-items-center"
                 style="
                    width: 35px;
                    height: 35px;
                    border: 2px solid #36b9cc;
                    color: #36b9cc;
                    font-weight: bold;
                    font-size: 18px;
                 ">

                !

            </div>

        </div>

        <h4 class="font-weight-bold text-gray-800 mb-0">

            Tulis Pengumuman

        </h4>

    </div>

    <!-- Textarea -->
    <div class="card shadow border-0"
         style="
            border-radius: 20px;
            max-width: 900px;
            width: 100%;
        ">

        <div class="card-body p-4">

            <textarea 
                id="isiPengumuman"
                class="form-control border-0 shadow-none"
                rows="4"
                placeholder="Tulis Disini..............">
            </textarea>

            <div class="d-flex justify-content-end align-items-center mt-3">

                <button id="btnKirimPengumuman"
                        class="btn btn-primary rounded-circle"
                        style="
                            width:45px;
                            height:45px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">

                    <i class="fas fa-paper-plane"></i>

                </button>

            </div>

        </div>

    </div>

</div>

<!-- ========================================= -->
<!-- MODAL KONFIRMASI BUKA PUTARAN -->
<!-- ========================================= -->
<div class="modal fade"
     id="modalBukaPutaran"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered"
         role="document">

        <div class="modal-content"
             style="
                border-radius: 25px;
                overflow: hidden;
             ">

            <!-- Header -->
            <div class="modal-header border-0 py-4">

                <div class="d-flex align-items-center">

                    <div class="rounded-circle d-flex justify-content-center align-items-center mr-3"
                         style="
                            width: 50px;
                            height: 50px;
                            border: 4px solid #36b9cc;
                            color: #36b9cc;
                            font-size: 26px;
                            font-weight: bold;
                         ">

                        !

                    </div>

                    <h4 class="font-weight-bold mb-0">
                        Format buka siklus baru PDRB
                    </h4>

                </div>

                <button type="button"
                        class="close"
                        data-dismiss="modal">

                    <span aria-hidden="true"
                          style="
                            font-size: 40px;
                            color: #cfcfcf;
                          ">

                        &times;

                    </span>

                </button>

            </div>

            <hr class="my-0">

           <!-- Body -->
            <div class="modal-body px-5 py-4">


                <!-- Tahun -->
                <div class="row align-items-center mb-4">

                    <div class="col-md-5">

                        <label class="font-weight-bold text-gray-700 mb-0"
                            style="font-size:18px;">

                            Tahun

                        </label>

                    </div>


                    <div class="col-md-3">

                        <select class="form-control"
                                id="tahun"
                                style="
                                    height:45px;
                                    border-radius:12px;
                                    font-size:18px;
                                ">

                            @for($tahun = 2010; $tahun <= 2028; $tahun++)

                                <option>{{ $tahun }}</option>

                            @endfor

                        </select>

                    </div>


                </div>


                <!-- Quartal -->
                <div class="row align-items-center mb-4">


                    <div class="col-md-5">

                        <label class="font-weight-bold text-gray-700 mb-0"
                            style="font-size:18px;">

                            Quartal

                        </label>

                    </div>



                    <div class="col-md-3">


                        <select class="form-control"
                                id="triwulan"
                                style="
                                    border-radius:15px;
                                    height:50px;
                                    font-size:18px;
                                ">

                            <option value="1">Q1</option>
                            <option value="2">Q2</option>
                            <option value="3">Q3</option>
                            <option value="4">Q4</option>

                        </select>


                    </div>



                    <div class="col-md-4">


                        <button id="btnBukaQuartal"
                                class="btn btn-success btn-block"
                                style="
                                    height:45px;
                                    border-radius:12px;
                                    font-size:15px;
                                    font-weight:600;
                                ">

                            Buka Quartal Baru

                        </button>


                    </div>


                </div>




                <!-- Putaran -->
                <div class="row align-items-center">


                    <div class="col-md-5">


                        <label class="font-weight-bold text-gray-700 mb-0"
                            style="font-size:18px;">

                            Putaran Berikutnya

                        </label>


                    </div>



                    <div class="col-md-3">


                        <select class="form-control"
                                id="putaran"
                                style="
                                    border-radius:15px;
                                    height:50px;
                                    font-size:18px;
                                ">

                            @for($i = 0; $i <= 10; $i++)

                                <option value="{{ $i }}">
                                    Putaran {{ $i }}
                                </option>

                            @endfor

                        </select>


                    </div>



                    <div class="col-md-4">


                        <button id="btnBukaPutaran"
                                class="btn btn-success btn-block"
                                style="
                                    height:45px;
                                    border-radius:12px;
                                    font-size:15px;
                                    font-weight:600;
                                ">


                            Buka Putaran Baru


                        </button>


                    </div>


                </div>


            </div>

            <hr class="my-0">

        </div>

    </div>

</div>

<!-- ========================================= -->
<!-- MODAL KONFIRMASI TUTUP PUTARAN -->
<!-- ========================================= -->
<div class="modal fade"
     id="modalTutupPutaran"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered"
         role="document"
         style="max-width: 450px;">

        <div class="modal-content border-0 shadow"
             style="border-radius: 20px; position: relative;">

             <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                          style="
                        position: absolute;
                        top: 15px;
                        right: 18px;
                        z-index: 10;
                        outline: none;
                    ">

                    <span aria-hidden="true"
                        style="
                            font-size: 32px;
                            color: #cfcfcf;
                            font-weight: 400;
                        ">
                        &times;
                    </span>

                </button>
            

            <div class="modal-body text-center px-5 py-5">

                <!-- Icon Tanda Tanya -->
                <div class="d-flex justify-content-center mb-4">

                    <div class="d-flex justify-content-center align-items-center"
                         style="
                            width: 70px;
                            height: 70px;
                            border-radius: 50%;
                            border: 3px solid #e74a3b;
                            color: #e74a3b;
                            font-size: 32px;
                            font-weight: bold;
                         ">

                        ?

                    </div>

                </div>

                <!-- Judul -->
                <h5 class="font-weight-bold text-gray-800 mb-3">
                    Yakin ingin menutup putaran?
                </h5>

                <!-- Keterangan -->
                <p class="text-gray-600 mb-4"
                   style="font-size: 14px; line-height: 1.7;">

                    Setelah putaran ditutup, proses rekonsiliasi pada
                    putaran ini tidak dapat dilakukan kembali.

                </p>

                <!-- Tombol -->
                <div class="d-flex justify-content-center">

                    <button type="button"
                            class="btn btn-light px-4 mr-2"
                            data-dismiss="modal"
                            style="border-radius: 10px;">

                        Tidak

                    </button>

                    <button id="btnTutupPutaran"
                            type="button"
                            class="btn btn-danger px-4"
                            style="border-radius: 10px;">

                        Ya, Tutup

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@section('scripts')

<script>


// Buka Quartal
document.getElementById('btnBukaQuartal')
.addEventListener('click', function(){

    let tahun = document.getElementById('tahun').value;
    let triwulan = document.getElementById('triwulan').value;


    axios.post('/api/rekonsiliasi/buka-quartal', {

        tahun: tahun,
        triwulan: triwulan

    })
    .then(response => {

        notifSukses(response.data.message ?? 'Quartal berhasil dibuka');

        ambilStatusRekonsiliasi();

    })
    .catch(error => {

        console.error(error);

        notifError(
            error.response?.data?.message 
            ?? 'Gagal membuka quartal'
        );

    });

});


// Buka Putaran
document.getElementById('btnBukaPutaran')
.addEventListener('click', function(){


    // cek status dulu
    axios.get('/api/rekonsiliasi/status')

    .then(response => {


        let data = response.data;

        // kalau masih ada putaran berjalan
        if(data.putaran_aktif){

            Swal.fire({

                icon: 'warning',

                title: 'Putaran masih berlangsung',

                text: 'Silakan tutup putaran yang sedang berjalan terlebih dahulu sebelum membuka quartal atau putaran baru.',

                confirmButtonText: 'Mengerti'

            });


            return;

        }



        // kalau tidak ada putaran aktif, buka putaran baru
        axios.post('/api/rekonsiliasi/buka-putaran')

        .then(response => {


            notifSukses(
                response.data.message ?? 
                'Putaran berhasil dibuka'
            );


            ambilStatusRekonsiliasi();


        })

        .catch(error => {


            console.error(error);


            notifError(
                error.response?.data?.message 
                ?? 'Gagal membuka putaran'
            );


        });



    })

    .catch(error => {


        console.error(error);


        notifError(
            'Gagal mengecek status rekonsiliasi'
        );


    });


});


// Tutup Putaran
document.getElementById('btnTutupPutaran')
.addEventListener('click', function(){

    axios.post('/api/rekonsiliasi/tutup')
    .then(response => {

        notifSukses(response.data.message ?? 'Putaran berhasil ditutup');

        ambilStatusRekonsiliasi();

    })
    .catch(error => {

        console.error(error);

        notifError(
            error.response?.data?.message 
            ?? 'Gagal menutup putaran'
        );

    });

});


function ambilStatusRekonsiliasi(){

    axios.get('/api/rekonsiliasi/status')
    .then(response => {

        let data = response.data;


        // kotak kanan
        document.getElementById('statusTahun').innerHTML =
            data.rekonsiliasi.tahun;


        document.getElementById('statusTriwulan').innerHTML =
            'Q' + data.rekonsiliasi.triwulan;


        if(data.putaran_aktif){

            document.getElementById('statusPutaran').innerHTML =
                data.putaran_aktif.nomor;


            document.getElementById('statusBadge').innerHTML =
                'Rekonsiliasi Sedang Berlangsung';

        } else {

            document.getElementById('statusPutaran').innerHTML =
                data.putaran_terakhir?.nomor ?? '-';


            document.getElementById('statusBadge').innerHTML =
                'Rekonsiliasi Ditutup';

        }


        // modal
        document.getElementById('tahun').value =
            data.rekonsiliasi.tahun;


        document.getElementById('triwulan').value =
            data.rekonsiliasi.triwulan;


        if(data.putaran_terakhir){

            document.getElementById('putaran').value =
                data.putaran_terakhir.nomor + 1;

        }


    })

    .catch(error => {

        console.log(error);

    });

}


document
.getElementById('btnKirimPengumuman')
.addEventListener('click', function(){


    let isi =
        document.getElementById('isiPengumuman').value;



    if(!isi.trim()){

        alert('Pengumuman masih kosong');

        return;

    }



    axios.post(
        '/api/pengumuman',
        {

            isi: isi

        }
    )

    .then(response=>{


        Swal.fire({

            icon: 'success',

            title: 'Berhasil',

            text: 'Pengumuman berhasil dikirim',

            showConfirmButton: false,

            timer: 1500

        });



        document
        .getElementById('isiPengumuman')
        .value = '';


        })

        .catch(error=>{


        Swal.fire({

            icon: 'error',

            title: 'Gagal',

            text: 'Pengumuman gagal dikirim'

        });


    });



});

// jalankan saat halaman dibuka
ambilStatusRekonsiliasi();

</script>

@endsection
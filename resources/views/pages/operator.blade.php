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

                            Setelah dibuka, putaran atau triwulan sebelumnya
                            tidak dapat dibuka kembali. Pilih tahun terlebih
                            dahulu untuk melanjutkan proses pembukaan putaran.

                        </div>

                        <!-- Pilih Tahun -->
                        <div>

                            <small class="text-gray-500 font-weight-bold d-block mb-2">

                                PILIH TAHUN

                            </small>

                            <select class="form-control"
                                    style="
                                        border-radius: 15px;
                                        height: 50px;
                                    ">

                                <option selected>
                                    2024
                                </option>

                                <option>
                                    2025
                                </option>

                                <option>
                                    2026
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- Tombol -->
                    <button class="btn btn-success btn-block py-2 mt-4"
                            data-toggle="modal"
                            data-target="#modalPutaran"
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

                                <h3 class="font-weight-bold text-dark mb-0">
                                    2026
                                </h3>

                            </div>

                            <div class="col-4">

                                <small class="text-muted d-block mb-2">
                                    TRIWULAN
                                </small>

                                <h3 class="font-weight-bold text-dark mb-0">
                                    Q2
                                </h3>

                            </div>

                            <div class="col-4">

                                <small class="text-muted d-block mb-2">
                                    PUTARAN
                                </small>

                                <h3 class="font-weight-bold text-dark mb-0">
                                    1
                                </h3>

                            </div>

                        </div>

                        <div class="text-center mt-4">

                            <span class="badge badge-success px-4 py-2"
                                style="
                                    border-radius: 20px;
                                    font-size: 14px;
                                ">

                                Rekonsiliasi Sedang Berlangsung

                            </span>

                        </div>

                    </div>

                    <!-- Tombol -->
                    <button class="btn btn-danger btn-block py-2 mt-4"
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
         ">

        <div class="card-body p-4">

            <textarea class="form-control border-0 shadow-none"
                      rows="5"
                      placeholder="Tulis Disini.............."
                      style="
                        resize: none;
                        font-size: 14px;
                      "></textarea>

            <div class="d-flex justify-content-end align-items-center mt-3">

                <button class="btn btn-link p-0 mr-3"
                        style="
                            font-size: 35px;
                            color: #36b9cc;
                         ">

                    <i class="far fa-smile"></i>

                </button>

                <button class="btn btn-link p-0"
                        style="
                            font-size: 40px;
                            color: #36b9cc;
                         ">

                    <i class="fas fa-paper-plane"></i>

                </button>

            </div>

        </div>

    </div>

</div>

<!-- ========================================= -->
<!-- MODAL -->
<!-- ========================================= -->
<div class="modal fade"
     id="modalPutaran"
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
            <div class="modal-body py-4 px-5">

                <!-- Tahun -->
                <div class="row align-items-center mb-4">

                    <div class="col-md-4">
                        <h4 class="font-weight-bold mb-0">
                            Tahun
                        </h4>
                    </div>

                    <div class="col-md-1 text-center">
                        <h4 class="mb-0">:</h4>
                    </div>

                    <div class="col-md-3">

                        <select class="form-control"
                                style="
                                    border-radius: 15px;
                                    height: 50px;
                                    font-size: 24px;
                                ">

                            <option>2024</option>
                            <option>2025</option>
                            <option>2026</option>

                        </select>

                    </div>

                </div>

                <!-- Putaran -->
                <div class="row align-items-center mb-4">

                    <div class="col-md-4">
                        <h4 class="font-weight-bold mb-0">
                            Putaran Terakhir
                        </h4>
                    </div>

                    <div class="col-md-1 text-center">
                        <h4 class="mb-0">:</h4>
                    </div>

                    <div class="col-md-3">

                        <select class="form-control"
                                style="
                                    border-radius: 15px;
                                    height: 50px;
                                    font-size: 22px;
                                ">

                            <option>Putaran 1</option>
                            <option selected>Putaran 2</option>
                            <option>Putaran 3</option>
                            <option>Putaran 4</option>

                        </select>

                    </div>

                    <div class="col-md-4">

                        <button class="btn btn-info btn-block py-3"
                                style="
                                    border-radius: 18px;
                                    font-size: 20px;
                                    box-shadow: 0 5px 10px rgba(0,0,0,0.15);
                                ">

                            Buka Putaran Baru

                        </button>

                    </div>

                </div>

                <!-- Quartal -->
                <div class="row align-items-center">

                    <div class="col-md-4">
                        <h4 class="font-weight-bold mb-0">
                            Quartal Terakhir
                        </h4>
                    </div>

                    <div class="col-md-1 text-center">
                        <h4 class="mb-0">:</h4>
                    </div>

                    <div class="col-md-3">

                        <select class="form-control"
                                style="
                                    border-radius: 15px;
                                    height: 50px;
                                    font-size: 24px;
                                ">

                            <option>Q1</option>
                            <option>Q2</option>
                            <option>Q3</option>
                            <option>Q4</option>

                        </select>

                    </div>

                    <div class="col-md-4">

                        <button class="btn btn-info btn-block py-3"
                                style="
                                    border-radius: 18px;
                                    font-size: 20px;
                                    box-shadow: 0 5px 10px rgba(0,0,0,0.15);
                                ">

                            Buka Quartal Baru

                        </button>

                    </div>

                </div>

            </div>

            <hr class="my-0">

            <!-- Footer -->
            <div class="modal-footer border-0 py-4 px-5 justify-content-start">

                <button class="btn btn-success px-5 py-3 mr-3"
                        style="
                            border-radius: 20px;
                            font-size: 22px;
                            font-weight: bold;
                        ">

                    Konfirmasi

                </button>

                <button class="btn btn-outline-success px-5 py-3"
                        data-dismiss="modal"
                        style="
                            border-radius: 20px;
                            font-size: 22px;
                            font-weight: bold;
                        ">

                    Batalkan

                </button>

            </div>

        </div>

    </div>

</div>

@endsection
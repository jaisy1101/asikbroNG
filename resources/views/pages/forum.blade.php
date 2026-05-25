@extends('layouts.app')

@section('content')

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">

    <h1 class="h3 mb-0 text-gray-800">
        Forum
    </h1>

</div>

<!-- List Forum -->
<div class="row">

    <!-- Komentar 1 -->
    <div class="col-md-6 mb-4">

        <div class="card shadow border-0">

            <!-- Header -->
            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">

                    <img src="{{ asset('assets/img/bps.png') }}"
                         alt="BPS"
                         style="
                            width: 35px;
                            height: 35px;
                            object-fit: contain;
                         ">

                    <span class="ml-2 font-weight-bold text-gray-700">

                        BPS Gowa

                    </span>

                </div>

                <small class="text-gray-500">

                    20/05/2024 12:19 PM

                </small>

            </div>

            <!-- Body -->
            <div class="card-body">

                <div class="p-4 rounded"
                     style="
                        background-color: #003b63;
                        color: white;
                        font-size: 15px;
                        line-height: 1.6;
                     ">

                    Jasa pendidikan pada lapangan usaha masih merah
                    di Kabupaten Gowa, tolong direvisi.

                </div>

            </div>

            <!-- Footer -->
            <div class="card-footer bg-white text-right border-0">

                <button class="btn btn-success btn-sm">

                    Beri Komentar

                </button>

            </div>

        </div>

    </div>

    <!-- Komentar 2 -->
    <div class="col-md-6 mb-4">

        <div class="card shadow border-0">

            <!-- Header -->
            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">

                    <img src="{{ asset('assets/img/bps.png') }}"
                         alt="BPS"
                         style="
                            width: 35px;
                            height: 35px;
                            object-fit: contain;
                         ">

                    <span class="ml-2 font-weight-bold text-gray-700">

                        BPS Bone

                    </span>

                </div>

                <small class="text-gray-500">

                    22/05/2024 12:19 PM

                </small>

            </div>

            <!-- Body -->
            <div class="card-body">

                <div class="p-4 rounded"
                     style="
                        background-color: #003b63;
                        color: white;
                        font-size: 15px;
                        line-height: 1.6;
                     ">

                    Nilai ADHK triwulan II masih berbeda dengan hasil
                    rekonsiliasi provinsi. Mohon dicek kembali.

                </div>

            </div>

            <!-- Footer -->
            <div class="card-footer bg-white text-right border-0">

                <button class="btn btn-success btn-sm">

                    Beri Komentar

                </button>

            </div>

        </div>

    </div>

    <!-- Komentar 3 -->
    <div class="col-md-6 mb-4">

        <div class="card shadow border-0">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">

                    <img src="{{ asset('assets/img/bps.png') }}"
                         alt="BPS"
                         style="
                            width: 35px;
                            height: 35px;
                            object-fit: contain;
                         ">

                    <span class="ml-2 font-weight-bold text-gray-700">

                        BPS Makassar

                    </span>

                </div>

                <small class="text-gray-500">

                    23/05/2024 08:42 AM

                </small>

            </div>

            <div class="card-body">

                <div class="p-4 rounded"
                     style="
                        background-color: #003b63;
                        color: white;
                        font-size: 15px;
                        line-height: 1.6;
                     ">

                    Tabel distribusi konsumsi pemerintah sudah diperbaiki
                    dan diunggah ulang.

                </div>

            </div>

            <div class="card-footer bg-white text-right border-0">

                <button class="btn btn-success btn-sm">

                    Beri Komentar

                </button>

            </div>

        </div>

    </div>

    <!-- Komentar 4 -->
    <div class="col-md-6 mb-4">

        <div class="card shadow border-0">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">

                    <img src="{{ asset('assets/img/bps.png') }}"
                         alt="BPS"
                         style="
                            width: 35px;
                            height: 35px;
                            object-fit: contain;
                         ">

                    <span class="ml-2 font-weight-bold text-gray-700">

                        BPS Parepare

                    </span>

                </div>

                <small class="text-gray-500">

                    23/05/2024 10:10 AM

                </small>

            </div>

            <div class="card-body">

                <div class="p-4 rounded"
                     style="
                        background-color: #003b63;
                        color: white;
                        font-size: 15px;
                        line-height: 1.6;
                     ">

                    Mohon konfirmasi untuk revisi komponen transportasi
                    pada ADHB TW II.

                </div>

            </div>

            <div class="card-footer bg-white text-right border-0">

                <button class="btn btn-success btn-sm">

                    Beri Komentar

                </button>

            </div>

        </div>

    </div>

</div>

<!-- Input Komentar -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="form-group mb-3">

            <label class="font-weight-bold">
                Tulis Komentar
            </label>

            <textarea class="form-control"
                      rows="4"
                      placeholder="Tuliskan komentar atau catatan rekon di sini..."></textarea>

        </div>

        <div class="text-right">

            <button class="btn btn-primary">

                <i class="fas fa-paper-plane mr-2"></i>

                Kirim

            </button>

        </div>

    </div>

</div>

@endsection
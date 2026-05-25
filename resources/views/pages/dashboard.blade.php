@extends('layouts.app')

@section('content')

<!-- ========================================= -->
<!-- TENTANG APLIKASI -->
<!-- ========================================= -->
<div class="mb-5">

    <!-- Title -->
    <div class="d-flex align-items-center mb-4">

        <div class="bg-success text-white px-3 py-1 mr-2 shadow-sm"
             style="
                border-radius: 8px;
                font-size: 16px;
                font-weight: bold;
             ">

            Tentang

        </div>

        <h2 class="font-weight-bold text-dark mb-0">

            Aplikasi

        </h2>

    </div>

    <!-- Content -->
    <div class="row">

        <!-- Kiri -->
        <div class="col-lg-7 mb-4">

            <div class="card border-0 shadow-sm h-100"
                 style="
                    border-radius: 25px;
                 ">

                <div class="card-body p-4"
                     style="
                        font-size: 18px;
                        line-height: 1.9;
                        color: #3a3b45;
                     ">

                    <p>

                        Platform ini merupakan aplikasi berbasis web
                        yang dirancang untuk mendukung proses
                        rekonsiliasi PDRB triwulanan di lingkungan
                        badan pusat statistik.

                    </p>

                    <p class="mb-0">

                        Aplikasi ini diharapkan dapat menjadi solusi
                        bagi pegawai BPS dalam meningkatkan efisiensi
                        dan akurasi proses rekonsiliasi PDRB serta
                        mendukung pengambilan keputusan berbasis data.

                    </p>

                </div>

            </div>

        </div>

        <!-- Kanan -->
        <div class="col-lg-5 mb-4">

            <div class="position-relative h-100">

                <!-- Card -->
                <div class="card border-0 shadow h-100 d-flex justify-content-center align-items-center"
                     style="
                        border-radius: 25px;
                        background: linear-gradient(135deg, #42a5f5, #2b8cd8);
                        min-height: 260px;
                        overflow: hidden;
                     ">

                    <!-- Circle Decoration -->
                    <div style="
                            position: absolute;
                            width: 220px;
                            height: 220px;
                            border-radius: 50%;
                            background: rgba(255,255,255,0.05);
                            top: -60px;
                            left: -60px;
                        ">
                    </div>

                    <div style="
                            position: absolute;
                            width: 180px;
                            height: 180px;
                            border-radius: 50%;
                            background: rgba(255,255,255,0.04);
                            bottom: -50px;
                            right: -50px;
                        ">
                    </div>

                    <!-- Text -->
                    <div class="text-center text-white">

                        <div style="
                                font-size: 22px;
                                letter-spacing: 3px;
                                font-weight: bold;
                            ">

                            PUTARAN 1

                        </div>

                    </div>

                </div>

                <!-- Badge -->
                <div class="position-absolute shadow"
                     style="
                        top: -10px;
                        right: -10px;
                        background: #7bc043;
                        color: white;
                        padding: 18px 30px;
                        border-radius: 0 0 0 70px;
                        text-align: center;
                        font-weight: bold;
                     ">

                    <div style="font-size: 14px;">
                        Rekon 2025
                    </div>

                    <div style="
                            font-size: 40px;
                            line-height: 1;
                         ">

                        Q1

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- ========================================= -->
<!-- PENGUMUMAN -->
<!-- ========================================= -->
<div>

    <!-- Header -->
    <div class="d-flex align-items-center mb-4">

        <div class="rounded-circle d-flex justify-content-center align-items-center mr-3"
             style="
                width: 40px;
                height: 40px;
                border: 2px solid #36b9cc;
                color: #36b9cc;
                font-size: 18px;
                font-weight: bold;
             ">

            <i class="fas fa-bullhorn"></i>

        </div>

        <h3 class="font-weight-bold text-gray-800 mb-0">

            Pengumuman

        </h3>

    </div>

    <!-- List Pengumuman -->
    <div class="card border-0 shadow-sm"
         style="
            border-radius: 25px;
         ">

        <div class="card-body p-0">

            <!-- Item -->
            <div class="d-flex align-items-center justify-content-between p-4 border-bottom">

                <div>

                    <h5 class="font-weight-bold text-dark mb-1">

                        Putaran 1 Dibuka

                    </h5>

                    <small class="text-gray-500">

                        Rekonsiliasi PDRB 2026 Q2 telah dimulai.

                    </small>

                </div>

                <div class="text-right">

                    <small class="text-gray-500">

                        20 Mei 2026

                    </small>

                </div>

            </div>

            <!-- Item -->
            <div class="d-flex align-items-center justify-content-between p-4 border-bottom">

                <div>

                    <h5 class="font-weight-bold text-dark mb-1">

                        Putaran 4 Ditutup

                    </h5>

                    <small class="text-gray-500">

                        Rekonsiliasi PDRB 2025 Q4 telah selesai.

                    </small>

                </div>

                <div class="text-right">

                    <small class="text-gray-500">

                        18 Mei 2026

                    </small>

                </div>

            </div>

            <!-- Item -->
            <div class="d-flex align-items-center justify-content-between p-4">

                <div>

                    <h5 class="font-weight-bold text-dark mb-1">

                        Putaran 3 Dibuka

                    </h5>

                    <small class="text-gray-500">

                        Monitoring data daerah telah diperbarui.

                    </small>

                </div>

                <div class="text-right">

                    <small class="text-gray-500">

                        12 Mei 2026

                    </small>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
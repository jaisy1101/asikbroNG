@extends('layouts.app')

@section('content')

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">

    <h1 class="h3 mb-0 text-gray-800">
        Monitoring
    </h1>

</div>

<!-- Filter Section -->
<div class="card shadow mb-4">

    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <!-- Toggle Group -->
            <div class="d-flex flex-wrap">

                <!-- Pengeluaran / Lapangan Usaha -->
                <div class="btn-group mr-3 mb-2" role="group">

                    <button type="button"
                            class="btn btn-primary">
                        Pengeluaran
                    </button>

                    <button type="button"
                            class="btn btn-outline-primary">
                        Lapangan Usaha
                    </button>

                </div>

                <!-- ADHB / ADHK -->
                <div class="btn-group mb-2" role="group">

                    <button type="button"
                            class="btn btn-success">
                        ADHB
                    </button>

                    <button type="button"
                            class="btn btn-outline-success">
                        ADHK
                    </button>

                </div>

            </div>

            <!-- Putaran & Rekon -->
            <div class="d-flex align-items-center mt-3 mt-md-0">

                <div class="mr-4">

                    <span class="font-weight-bold text-primary">
                        PUTARAN 0
                    </span>

                </div>

                <div>

                    <span class="font-weight-bold text-dark">
                        REKON 2026 Q2
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

            <table class="table table-bordered table-hover">

                <thead class="thead-light">

                    <tr>

                        <th>Kabupaten/Kota</th>
                        <th>Status</th>
                        <th>Tanggal Upload</th>
                        <th>Waktu</th>

                    </tr>

                </thead>

                <tbody>

                    <tr>
                        <td>Kota Makassar</td>
                        <td>
                            <span class="badge badge-success">
                                Sudah Upload
                            </span>
                        </td>
                        <td>12 Mei 2026</td>
                        <td>08:15 WITA</td>
                    </tr>

                    <tr>
                        <td>Kabupaten Gowa</td>
                        <td>
                            <span class="badge badge-warning">
                                Belum Upload
                            </span>
                        </td>
                        <td>-</td>
                        <td>-</td>
                    </tr>

                    <tr>
                        <td>Kabupaten Bone</td>
                        <td>
                            <span class="badge badge-success">
                                Sudah Upload
                            </span>
                        </td>
                        <td>12 Mei 2026</td>
                        <td>09:42 WITA</td>
                    </tr>

                    <tr>
                        <td>Kota Parepare</td>
                        <td>
                            <span class="badge badge-danger">
                                Revisi
                            </span>
                        </td>
                        <td>11 Mei 2026</td>
                        <td>15:30 WITA</td>
                    </tr>

                    <tr>
                        <td>Kabupaten Maros</td>
                        <td>
                            <span class="badge badge-success">
                                Sudah Upload
                            </span>
                        </td>
                        <td>12 Mei 2026</td>
                        <td>10:05 WITA</td>
                    </tr>

                    <tr>
                        <td>Kabupaten Wajo</td>
                        <td>
                            <span class="badge badge-warning">
                                Belum Upload
                            </span>
                        </td>
                        <td>-</td>
                        <td>-</td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
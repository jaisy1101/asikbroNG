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

        </div>

    </div>

</div>

<!-- Summary Cards -->
<div class="row mb-4">

    <!-- ADHB -->
    <div class="col-md-6 mb-3">

        <div class="card shadow border-left-warning h-100"
             style="cursor: pointer;"
             data-toggle="collapse"
             data-target="#detailADHB">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="font-weight-bold text-warning mb-1"
                             style="font-size: 22px;">

                            ADHB

                        </div>

                        <div class="text-gray-800"
                             style="font-size: 16px;">

                            Ada Selisih

                        </div>

                    </div>

                    <div>

                        <i class="fas fa-exclamation-triangle text-warning"
                           style="font-size: 32px;">
                        </i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ADHK -->
    <div class="col-md-6 mb-3">

        <div class="card shadow border-left-success h-100"
             style="cursor: pointer;"
             data-toggle="collapse"
             data-target="#detailADHK">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="font-weight-bold text-success mb-1"
                             style="font-size: 22px;">

                            ADHK

                        </div>

                        <div class="text-gray-800"
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

            <h6 class="m-0 font-weight-bold text-warning">
                Detail Integrasi ADHB
            </h6>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead class="thead-light">

                        <tr>

                            <th>Triwulan</th>
                            <th>Pengeluaran</th>
                            <th>Lapangan Usaha</th>
                            <th>Selisih</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td>TW I</td>
                            <td>12.450</td>
                            <td>12.450</td>
                            <td>0</td>

                            <td>
                                <span class="text-success font-weight-bold">
                                    ✅ Sinkron
                                </span>
                            </td>

                        </tr>

                        <tr>

                            <td>TW II</td>
                            <td>13.210</td>
                            <td>13.205</td>
                            <td>5</td>

                            <td>
                                <span class="text-warning font-weight-bold">
                                    ⚠ Ada Selisih
                                </span>
                            </td>

                        </tr>

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

            <h6 class="m-0 font-weight-bold text-success">
                Detail Integrasi ADHK
            </h6>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead class="thead-light">

                        <tr>

                            <th>Triwulan</th>
                            <th>Pengeluaran</th>
                            <th>Lapangan Usaha</th>
                            <th>Selisih</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td>TW I</td>
                            <td>11.320</td>
                            <td>11.320</td>
                            <td>0</td>

                            <td>
                                <span class="text-success font-weight-bold">
                                    ✅ Sinkron
                                </span>
                            </td>

                        </tr>

                        <tr>

                            <td>TW II</td>
                            <td>12.540</td>
                            <td>12.540</td>
                            <td>0</td>

                            <td>
                                <span class="text-success font-weight-bold">
                                    ✅ Sinkron
                                </span>
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection
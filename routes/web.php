<?php

use Illuminate\Support\Facades\Route;
use App\Services\Derived\DerivedPdrbService;

Route::get('/', function () {
    return view('pages.dashboard');
});

Route::view('/', 'pages.dashboard');

Route::view('/pengeluaran', 'pages.pengeluaran');

Route::view('/lapangan-usaha', 'pages.lapangan-usaha');

Route::view('/integrasi', 'pages.integrasi');

Route::view('/monitoring', 'pages.monitoring');

Route::view('/forum', 'pages.forum');

Route::view('/operator', 'pages.operator');

Route::view('/pengeluaran/unggah-tabel', 'pages.pengeluaran.unggah-tabel');

Route::view('/pengeluaran/daftar-tabel', 'pages.pengeluaran.daftar-tabel');

Route::view('/pengeluaran/perubahan-nilai', 'pages.pengeluaran.perubahan-nilai');

Route::view('/pengeluaran/hasil-konserda', 'pages.pengeluaran.hasil-konserda');

Route::view('/lapangan-usaha/unggah-tabel', 'pages.lapangan-usaha.unggah-tabel');

Route::view('/lapangan-usaha/daftar-tabel', 'pages.lapangan-usaha.daftar-tabel');

Route::view('/lapangan-usaha/perubahan-nilai', 'pages.lapangan-usaha.perubahan-nilai');

Route::view('/lapangan-usaha/hasil-konserda', 'pages.lapangan-usaha.hasil-konserda');

Route::view('/login-preview', 'auth.login');


Route::get('/test-distribusi', function (DerivedPdrbService $service) {

    $service->hitungDistribusi(
        1, // wilayah_id
        61  // periode_id
    ); 

    return "Membuat Tabel Distribusi selesai";

});

Route::get('/test-qtq', function(
    \App\Services\Derived\DerivedPdrbService $service
){

    $service->hitungQtQ(
        1, // wilayah_id
        63  // 2025 Q2
    );

    return "Membuat Tabel QTQ selesai";

});

Route::get('/test-yty', function(
    \App\Services\Derived\DerivedPdrbService $service
){

    $service->hitungYtY(
        1,
        63
    );

    return "YTY selesai";

});

Route::get('/test-ctc', function(
    \App\Services\Derived\DerivedPdrbService $service
){

    $service->hitungCtC(
        1,
        63
    );

    return "CTC selesai";

});

Route::get('/test-implisit', function(
    \App\Services\Derived\DerivedPdrbService $service
){

    $service->hitungImplisit(
        1,
        59
    );

    return "Implisit selesai";

});

Route::get('/test-implisit-qtq', function(
    \App\Services\Derived\DerivedPdrbService $service
){

    $service->hitungImplisitQtQ(
        1,
        63
    );

    return "Implisit QTQ selesai";

});

Route::get('/test-implisit-yty', function(
    \App\Services\Derived\DerivedPdrbService $service
){

    $service->hitungImplisitYtY(
        1,
        63
    );

    return "Implisit YTY selesai";

});
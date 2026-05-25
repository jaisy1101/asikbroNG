<?php

use Illuminate\Support\Facades\Route;

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

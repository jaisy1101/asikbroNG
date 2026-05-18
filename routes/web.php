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
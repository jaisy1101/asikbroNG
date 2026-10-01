<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Services\Derived\DerivedLapanganUsaha;
use App\Jobs\GenerateDerivedLapanganUsahaJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


/*
| Login
|--------------------------------------------------------------------------
*/

Route::view('/login', 'auth.login')
    ->name('login');


Route::post(
    '/login',
    [AuthController::class,'login']
);


Route::post('/logout', function(Request $request){

    Auth::logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();


    return redirect('/login');

});


/*
|--------------------------------------------------------------------------
| Halaman User + Admin
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
->group(function(){


    Route::view('/', 'pages.dashboard');


    Route::view(
        '/pengeluaran',
        'pages.pengeluaran'
    );


    Route::view(
        '/lapangan-usaha',
        'pages.lapangan-usaha'
    );


    Route::view(
        '/integrasi',
        'pages.integrasi'
    );


    Route::view(
        '/forum',
        'pages.forum'
    );


    Route::view(
        '/pengeluaran/unggah-tabel',
        'pages.pengeluaran.unggah-tabel'
    );


    Route::view(
        '/pengeluaran/daftar-tabel',
        'pages.pengeluaran.daftar-tabel'
    );


    Route::view(
        '/pengeluaran/perubahan-nilai',
        'pages.pengeluaran.perubahan-nilai'
    );


    Route::view(
        '/pengeluaran/hasil-konserda',
        'pages.pengeluaran.hasil-konserda'
    );


    Route::view(
        '/lapangan-usaha/unggah-tabel',
        'pages.lapangan-usaha.unggah-tabel'
    );


    Route::view(
        '/lapangan-usaha/daftar-tabel',
        'pages.lapangan-usaha.daftar-tabel'
    );


    Route::view(
        '/lapangan-usaha/perubahan-nilai',
        'pages.lapangan-usaha.perubahan-nilai'
    );


    Route::view(
        '/lapangan-usaha/hasil-konserda',
        'pages.lapangan-usaha.hasil-konserda'
    );


});



/*
|--------------------------------------------------------------------------
| Admin Only
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'admin'
])
->group(function(){


    Route::view(
        '/monitoring',
        'pages.monitoring'
    );


    Route::view(
        '/operator',
        'pages.operator'
    );


});
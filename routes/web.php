<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::view('/login', 'auth.login')
    ->name('login');


Route::post(
    '/login',
    [AuthController::class, 'login']
);


Route::post('/logout', function(Request $request){

    Auth::logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return redirect('/login');

});


/*
|--------------------------------------------------------------------------
| User & Admin Pages
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
->group(function(){


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::view(
        '/',
        'pages.dashboard'
    );


    /*
    |--------------------------------------------------------------------------
    | Modul Umum
    |--------------------------------------------------------------------------
    */

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



    /*
    |--------------------------------------------------------------------------
    | Pengeluaran
    |--------------------------------------------------------------------------
    */

    Route::prefix('pengeluaran')
    ->group(function(){


        Route::view(
            '/unggah-tabel',
            'pages.pengeluaran.unggah-tabel'
        );


        Route::view(
            '/daftar-tabel',
            'pages.pengeluaran.daftar-tabel'
        );


        Route::view(
            '/perubahan-nilai',
            'pages.pengeluaran.perubahan-nilai'
        );


        Route::view(
            '/hasil-konserda',
            'pages.pengeluaran.hasil-konserda'
        );


    });



    /*
    |--------------------------------------------------------------------------
    | Lapangan Usaha
    |--------------------------------------------------------------------------
    */

    Route::prefix('lapangan-usaha')
    ->group(function(){


        Route::view(
            '/unggah-tabel',
            'pages.lapangan-usaha.unggah-tabel'
        );


        Route::view(
            '/daftar-tabel',
            'pages.lapangan-usaha.daftar-tabel'
        );


        Route::view(
            '/perubahan-nilai',
            'pages.lapangan-usaha.perubahan-nilai'
        );


        Route::view(
            '/hasil-konserda',
            'pages.lapangan-usaha.hasil-konserda'
        );


    });


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
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\SubmissionListController;
use App\Http\Controllers\Api\SubmissionDataController;
use App\Http\Controllers\Api\PdrbTableController;
use App\Http\Controllers\Api\RekonsiliasiController;

Route::post('/login', function (Request $request) {

    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Email atau password salah'
        ], 401);
    }

    $token = $user->createToken('asikbro-token')->plainTextToken;

    return response()->json([
        'message' => 'Login berhasil',
        'token' => $token,
        'user' => $user
    ]);
});


Route::middleware('auth:sanctum')->group(function () {


});

//sementara buat testing, nanti dihapus
    Route::post(
        '/submission/upload',
        [SubmissionController::class, 'upload']
    );

    Route::get(
        '/submissions',
        [SubmissionListController::class, 'index']
    );

    Route::get(
        '/submissions/{id}/data',
        [SubmissionDataController::class, 'show']
    );

    Route::post(
        '/rekonsiliasi/buka-quartal',
        [RekonsiliasiController::class, 'bukaQuartalBaru']
    );

    Route::post(
        '/rekonsiliasi/buka-putaran',
        [RekonsiliasiController::class, 'bukaPutaranBaru']
    );

    Route::post(
        '/rekonsiliasi/tutup',
        [RekonsiliasiController::class, 'tutup']
    );

    Route::get(
        '/submissions/{id}/table',
        [PdrbTableController::class, 'showSubmission']
    );

    Route::get(
        '/pdrb/source-lapangan-usaha/{wilayah_id}/{jenis_tabel_id}',
        [PdrbTableController::class, 'showSourceLapanganUsaha']
    );

    Route::get(
        '/pdrb/source-pengeluaran/{wilayah_id}/{jenis_tabel_id}',
        [PdrbTableController::class, 'showSourcePengeluaran']
    );

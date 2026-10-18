<?php

namespace App\Http\Middleware;

use App\Services\Pdrb\SubmissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
| Halaman yang hanya boleh dibuka saat ada putaran rekonsiliasi
| berlangsung (Entri Tabel). Selain itu ditampilkan halaman pemberitahuan.
*/

class PutaranBerlangsungMiddleware
{
    public function __construct(
        private SubmissionService $submissions
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->submissions->putaranBerlangsung()) {

            return response()->view('pages.tanpa-putaran', [
                'judul' => 'Entri Tabel',
                'pesan' => 'Entri tabel hanya tersedia saat ada putaran rekonsiliasi yang berlangsung.',
            ]);
        }

        return $next($request);
    }
}

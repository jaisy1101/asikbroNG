<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;


class UserMiddleware
{

    public function handle(
        Request $request,
        Closure $next
    ): Response
    {

        if (!Auth::check() || Auth::user()->role_id != 2) {

            return response()->json([
                'message' => 'Akses ditolak'
            ], 403);

        }


        return $next($request);

    }

}
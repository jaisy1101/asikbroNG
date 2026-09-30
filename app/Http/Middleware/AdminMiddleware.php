<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class AdminMiddleware
{

    public function handle(
        Request $request,
        Closure $next
    ): Response
    {
        $user = $request->user();

        if (! $user || $user->role_id != 1) {
            return response()->json([
                'message' => 'Akses ditolak'
            ], 403);
        }

        return $next($request);
    }

}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'user') {
            abort(403, 'Fitur ini hanya dapat diakses oleh pengguna.');
        }

        return $next($request);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('voucher.admin_token');

        if ($expected === '') {
            return response()->json(['message' => 'Token admin belum dikonfigurasi.'], 503);
        }

        $given = (string) $request->header('X-Admin-Token', '');

        if (! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Tidak diizinkan.'], 401);
        }

        return $next($request);
    }
}
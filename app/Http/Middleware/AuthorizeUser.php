<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $role = ''): Response
    {
        $user = $request->user();

        // Cek apakah user sudah login dan memiliki role yang sesuai
        if (!$user || !$user->hasRole($role)) {
            abort(403, 'Forbidden. Kamu tidak punya akses ke halaman ini');
        }

        return $next($request);
    }
}

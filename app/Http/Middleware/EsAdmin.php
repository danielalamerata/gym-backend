<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsAdmin
{
    // Solo deja pasar a usuarios autenticados con rol admin
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->rol !== 'admin') {
            return response()->json([
                'message' => 'No tenés permiso para realizar esta acción',
            ], 403);
        }

        return $next($request);
    }
}

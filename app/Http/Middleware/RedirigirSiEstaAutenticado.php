<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirigirSiEstaAutenticado
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return $next($request);
    }
    public function handlecliente(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return $next($request);
    }
}
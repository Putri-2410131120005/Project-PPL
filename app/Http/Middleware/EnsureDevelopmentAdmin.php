<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDevelopmentAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->session()->get('dev_role');

        if (! $role) {
            return redirect()->route('dev.login');
        }

        if ($role !== 'admin') {
            return redirect()->route('dev.preview', $role);
        }

        return $next($request);
    }
}

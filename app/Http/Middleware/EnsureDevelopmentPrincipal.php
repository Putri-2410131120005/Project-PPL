<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDevelopmentPrincipal
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        if (! $request->session()->has('dev_role')) {
            return redirect()->route('dev.login');
        }
        abort_unless($request->session()->get('dev_role') === 'kepala-sekolah', 403);

        return $next($request);
    }
}

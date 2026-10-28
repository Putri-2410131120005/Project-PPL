<?php

namespace App\Http\Middleware;

use App\Services\GuruMapelAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureDevelopmentGuru
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        if (! Auth::guard('guru_dev')->check()) {
            return redirect()->route('dev.login');
        }
        abort_unless($request->session()->get('dev_role') === 'guru-mapel', 403);
        abort_unless((int) $request->session()->get('dev_pengguna_id') === (int) Auth::guard('guru_dev')->id(), 403);
        app(GuruMapelAccess::class)->actor(Auth::guard('guru_dev')->user());

        return $next($request);
    }
}

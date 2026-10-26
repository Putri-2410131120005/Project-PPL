<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\GuruMapelDemoIdentity;
use Illuminate\Http\Request;

class GuruMapelAuthController extends Controller
{
    public function create(Request $request)
    {
        return redirect()->route('dev.login');
    }

    public function switchDemo(Request $request, GuruMapelDemoIdentity $identity)
    {
        // Toggle only between the two server-defined demo accounts. No browser IDs.
        $identity->start($request, $request->session()->get('dev_guru_demo') === 'guru-1' ? 'guru-2' : 'guru-1');

        return redirect()->route('dev.guru-mapel.dashboard');
    }
}

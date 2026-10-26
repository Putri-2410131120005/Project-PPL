<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
class GuruMapelAuthController extends Controller
{
    public function create()
    {
        return redirect()->route('dev.login');
    }
}

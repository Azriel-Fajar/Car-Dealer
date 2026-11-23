<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleHomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect($request->user()->homePath());
    }
}

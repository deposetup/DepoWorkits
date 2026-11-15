<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->superAdminMi()) {
            return redirect()->route('admin.tenants.index');
        }

        return view('dashboard', ['tenant' => $user->tenant]);
    }
}

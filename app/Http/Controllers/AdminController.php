<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mall;
use App\Models\Store;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'malls' => Mall::count(),
            'stores' => Store::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}

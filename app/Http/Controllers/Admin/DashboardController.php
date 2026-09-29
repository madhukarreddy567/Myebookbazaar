<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Empty dashboard page (Phase 1). Modules arrive in later phases.
     */
    public function index(): View
    {
        return view('admin.dashboard');
    }
}

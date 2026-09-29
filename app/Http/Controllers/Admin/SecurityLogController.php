<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 6: security event viewer. Super Admin only.
 * Append-only; no edit or delete actions exist on purpose.
 */
class SecurityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = SecurityLog::with(['admin', 'user'])->latest('id');

        if ($request->filled('event')) {
            $query->where('event', 'like', '%'.str_replace(['%', '_'], '', $request->string('event')).'%');
        }

        if ($request->filled('admin_id')) {
            $query->where('admin_id', (int) $request->input('admin_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $logs = $query->paginate(25)->withQueryString();
        $admins = \App\Models\Admin::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.security-log.index', [
            'logs' => $logs,
            'admins' => $admins,
            'filters' => $request->only(['event', 'admin_id', 'from', 'to']),
        ]);
    }
}

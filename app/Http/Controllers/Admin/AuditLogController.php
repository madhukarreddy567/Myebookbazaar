<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 5: audit log viewer. Super Admin only.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('admin')->latest();

        if ($request->filled('admin_id')) {
            $query->where('admin_id', (int) $request->input('admin_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.str_replace(['%', '_'], '', $request->string('action')).'%');
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $logs = $query->paginate(25)->withQueryString();
        $admins = \App\Models\Admin::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.audit-log.index', [
            'logs' => $logs,
            'admins' => $admins,
            'filters' => $request->only(['admin_id', 'action', 'from', 'to']),
        ]);
    }
}

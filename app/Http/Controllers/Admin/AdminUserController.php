<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Models\Admin;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Phase 5: admin account management. Super Admin only.
 *
 * - Create staff accounts (role=staff). Nobody can create a super_admin here;
 *   there is exactly one super admin, managed outside this screen.
 * - Disable/enable accounts. Disabled accounts cannot log in.
 * - Delete: never yourself, never the last super_admin.
 */
class AdminUserController extends Controller
{
    public function index(): View
    {
        $admins = Admin::orderBy('role')->orderBy('name')->paginate(20);

        return view('admin.admins.index', [
            'admins' => $admins,
            'current' => auth('admin')->user(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $admin = Admin::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        Audit::record('admin.created', 'admin', $admin->id, [], [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'staff',
        ]);

        return back()->with('status', 'Staff account created for '.$admin->email.'.');
    }

    public function toggle(Admin $admin): RedirectResponse
    {
        $current = auth('admin')->user();

        if ($admin->id === $current->id) {
            return back()->withErrors(['admin' => 'You cannot disable your own account.']);
        }

        $admin->update(['is_active' => ! $admin->is_active]);

        Audit::record(
            $admin->is_active ? 'admin.enabled' : 'admin.disabled',
            'admin', $admin->id,
            ['is_active' => ! $admin->is_active],
            ['is_active' => $admin->is_active]
        );

        return back()->with('status', $admin->email.($admin->is_active ? ' enabled.' : ' disabled. They can no longer log in.'));
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        $current = auth('admin')->user();

        // Defense in depth: never leave the system with zero super admins.
        if ($admin->isSuperAdmin() && Admin::where('role', 'super_admin')->count() <= 1) {
            return back()->withErrors(['admin' => 'You cannot delete the last Super Admin.']);
        }

        if ($admin->id === $current->id) {
            return back()->withErrors(['admin' => 'You cannot delete your own account.']);
        }

        $email = $admin->email;
        $admin->delete();

        Audit::record('admin.deleted', 'admin', $admin->id, ['email' => $email], []);

        return back()->with('status', 'Admin account '.$email.' deleted.');
    }
}

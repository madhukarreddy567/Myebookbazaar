<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Phase 5: admin session management.
 *
 * Sessions use the database driver. An admin session is identified by the
 * guard's login key inside the serialized payload.
 */
class SessionController extends Controller
{
    protected function loginKey(): string
    {
        return 'login_admin_'.sha1(SessionGuard::class);
    }

    /**
     * @return array<int, array{id: string, ip: ?string, agent: ?string, last: int, current: bool}>
     */
    protected function mySessions(int $adminId, string $currentId): array
    {
        $key = $this->loginKey();
        $rows = DB::table('sessions')->orderByDesc('last_activity')->get();
        $mine = [];

        foreach ($rows as $row) {
            $payload = @unserialize(base64_decode($row->payload));
            if (! is_array($payload)) {
                continue;
            }
            if (($payload[$key] ?? null) != $adminId) {
                continue;
            }
            $mine[] = [
                'id' => $row->id,
                'ip' => $row->ip_address,
                'agent' => $row->user_agent,
                'last' => (int) $row->last_activity,
                'current' => $row->id === $currentId,
            ];
        }

        return $mine;
    }

    public function index(Request $request): View
    {
        $admin = auth('admin')->user();

        return view('admin.sessions.index', [
            'sessions' => $this->mySessions($admin->id, $request->session()->getId()),
        ]);
    }

    /**
     * Log out all other sessions. Requires the current password.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($data['password'], $admin->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        $currentId = $request->session()->getId();
        $mine = $this->mySessions($admin->id, $currentId);
        $otherIds = array_column(array_filter($mine, fn ($s) => ! $s['current']), 'id');

        if ($otherIds !== []) {
            DB::table('sessions')->whereIn('id', $otherIds)->delete();
        }

        Audit::record('admin.sessions_revoked', 'admin', $admin->id, [], [
            'revoked_count' => count($otherIds),
        ]);

        return back()->with('status', count($otherIds) === 0
            ? 'No other sessions found.'
            : count($otherIds).' other session(s) logged out.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GitHubService;
use App\Services\UpdateService;
use App\Services\UpdateException;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Phase 7: update dashboard (Super Admin only).
 * Checking is safe; APPLYING runs the guarded, rollback-protected flow.
 */
class UpdateController extends Controller
{
    public function index(UpdateService $updates, GitHubService $github)
    {
        return view('admin.updates.index', [
            'updates' => $updates,
            'github' => $github,
            'guard' => $updates->guard(),
            'lastCheck' => $updates->lastCheckAt(),
            'latestSha' => $updates->latestKnownSha(),
            'release' => $updates->latestRelease(),
            'freeSpace' => $updates->freeSpaceLabel(),
            'inProgress' => $updates->updateInProgress(),
        ]);
    }

    public function check(UpdateService $updates)
    {
        $result = $updates->checkForUpdates();

        return redirect()->route('reddy.updates.index')
            ->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    public function testGithub(GitHubService $github)
    {
        $result = $github->testConnection();

        Audit::record('settings.test_github', 'settings', null, [], [
            'result' => $result['ok'] ? 'connected' : 'failed',
        ]);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Explicit confirmation screen: version diff + backup warning.
     */
    public function confirm(UpdateService $updates)
    {
        $result = $updates->checkForUpdates();

        if (! $result['ok'] || ! $result['available']) {
            return redirect()->route('reddy.updates.index')
                ->with('error', $result['message'] ?: 'No update is available.');
        }

        if ($guard = $updates->guard($result['latest_sha'])) {
            return redirect()->route('reddy.updates.index')->with('error', $guard);
        }

        return view('admin.updates.confirm', [
            'updates' => $updates,
            'sha' => $result['latest_sha'],
            'release' => $result['release'],
        ]);
    }

    public function apply(Request $request, UpdateService $updates)
    {
        $request->validate([
            'sha' => ['required', 'string', 'regex:/^[0-9a-f]{40}$/i'],
            'confirm_backup' => ['accepted'],
            'password' => ['required', 'string'],
        ], [
            'confirm_backup.accepted' => 'You must confirm that a backup will be taken and the site will briefly go into maintenance.',
            'password.required' => 'Re-enter your admin password to authorize this update.',
        ]);

        // The password is checked and never stored or logged anywhere.
        $admin = auth('admin')->user();
        if (! $admin || ! Hash::check($request->string('password')->toString(), $admin->password)) {
            return back()
                ->withErrors(['password' => 'The password you entered does not match your admin password.'])
                ->withInput($request->except('password'));
        }

        try {
            $updates->applyUpdate($request->string('sha')->toString());
        } catch (UpdateException $e) {
            return redirect()->route('reddy.updates.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('reddy.updates.index')
            ->with('status', 'Update applied successfully. The site is back online.');
    }
}

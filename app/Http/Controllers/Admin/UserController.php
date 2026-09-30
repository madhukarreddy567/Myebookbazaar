<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Order;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Phase 5: customer management.
 *
 * Viewing is allowed for Staff; blocking/unblocking is Super Admin only
 * (enforced here, since it is an account-safety action).
 */
class UserController extends Controller
{
    public function index(\Illuminate\Http\Request $request): View
    {
        $query = User::withCount(['orders' => fn ($q) => $q->where('status', Order::STATUS_PAID)])
            ->latest();

        if ($request->filled('q')) {
            $q = '%'.str_replace(['%', '_'], '', $request->string('q')).'%';
            $query->where(fn ($w) => $w->where('name', 'like', $q)->orWhere('email', 'like', $q));
        }

        if ($request->filled('country')) {
            $query->where('country_code', $request->string('country'));
        }

        if ($request->filled('region') && in_array((string) $request->string('region'), ['IN', 'INTL'], true)) {
            $query->where('pricing_region', $request->string('region'));
        }

        if ($request->filled('blocked') && in_array((string) $request->string('blocked'), ['0', '1'], true)) {
            $query->where('is_blocked', $request->string('blocked'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        $users = $query->paginate(20)->withQueryString();
        $countries = User::select('country_code', 'country_name')
            ->distinct()->orderBy('country_name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'countries' => $countries,
            'filters' => $request->only(['q', 'country', 'region', 'blocked', 'from']),
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['orders' => fn ($q) => $q->latest(), 'orders.items']);

        $spent = $user->orders->where('status', Order::STATUS_PAID)
            ->groupBy('currency')
            ->map(fn ($orders) => $orders->sum('grand_total'));

        $downloads = \App\Models\DownloadLog::where('user_id', $user->id)
            ->whereNotNull('downloaded_at')
            ->with('book')->orderByDesc('downloaded_at')->limit(10)->get();

        return view('admin.users.show', [
            'user' => $user,
            'spentInr' => (float) ($spent['INR'] ?? 0),
            'spentUsd' => (float) ($spent['USD'] ?? 0),
            'downloads' => $downloads,
            'countries' => Country::orderBy('name')->get(['iso_code', 'name']),
        ]);
    }

    /**
     * Change a customer's country (Super Admin only). The pricing region is
     * recomputed from the new country and the change is audited.
     * Customers can never change their own country.
     */
    public function updateCountry(\Illuminate\Http\Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,iso_code'],
        ]);

        $country = Country::where('iso_code', strtoupper($validated['country_code']))->firstOrFail();
        $region = app(SettingsService::class)->regionForCountry($country->iso_code);

        $old = [
            'country_code' => $user->country_code,
            'country_name' => $user->country_name,
            'pricing_region' => $user->pricing_region,
        ];

        $user->update([
            'country_code' => $country->iso_code,
            'country_name' => $country->name,
            'pricing_region' => $region,
        ]);

        Audit::record('user.country_changed', 'user', $user->id, $old, [
            'country_code' => $country->iso_code,
            'country_name' => $country->name,
            'pricing_region' => $region,
        ]);

        return back()->with('status', "Country updated to {$country->name}. Pricing region is now ".($region === 'IN' ? 'India (INR)' : 'International (USD)').'.');
    }

    public function block(User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        if ($user->is_blocked) {
            return back()->with('status', 'This account is already blocked.');
        }

        $user->update(['is_blocked' => true]);
        Audit::record('user.blocked', 'user', $user->id, ['is_blocked' => false], ['is_blocked' => true]);

        return back()->with('status', $user->email.' has been blocked and can no longer log in.');
    }

    public function unblock(User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $user->update(['is_blocked' => false]);
        Audit::record('user.unblocked', 'user', $user->id, ['is_blocked' => true], ['is_blocked' => false]);

        return back()->with('status', $user->email.' has been unblocked.');
    }

    /**
     * CSV export of users (respects the same filters as the list).
     */
    public function export(\Illuminate\Http\Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = User::latest();

        if ($request->filled('q')) {
            $q = '%'.str_replace(['%', '_'], '', $request->string('q')).'%';
            $query->where(fn ($w) => $w->where('name', 'like', $q)->orWhere('email', 'like', $q));
        }

        if ($request->filled('country')) {
            $query->where('country_code', $request->string('country'));
        }

        if ($request->filled('region') && in_array((string) $request->string('region'), ['IN', 'INTL'], true)) {
            $query->where('pricing_region', $request->string('region'));
        }

        if ($request->filled('blocked') && in_array((string) $request->string('blocked'), ['0', '1'], true)) {
            $query->where('is_blocked', $request->string('blocked'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        $filename = 'users-'.now('Asia/Kolkata')->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Country', 'Pays in', 'Blocked', 'Email verified', 'Joined (IST)']);

            $query->orderBy('id')->chunk(500, function ($users) use ($out) {
                foreach ($users as $user) {
                    fputcsv($out, [
                        $user->name,
                        $user->email,
                        $user->country_name ?? $user->country_code,
                        $user->pricing_region === 'IN' ? 'INR' : 'USD',
                        $user->is_blocked ? 'yes' : 'no',
                        $user->hasVerifiedEmail() ? 'yes' : 'no',
                        $user->created_at->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function ensureSuperAdmin(): void
    {
        if (! auth('admin')->user()?->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can block or unblock accounts.');
        }
    }
}

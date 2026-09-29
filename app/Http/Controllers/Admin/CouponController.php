<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Phase 4: coupon management. Every change is audited. The show page
 * doubles as the usage-stats page.
 */
class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::query()
            ->withCount('usages')
            ->when(request('q'), fn ($q, $term) => $q->where('code', 'like', '%'.mb_strtoupper($term).'%'))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function show(Coupon $coupon): View
    {
        $coupon->loadCount('usages');

        $usages = $coupon->usages()
            ->with(['user', 'order'])
            ->orderByDesc('used_at')
            ->paginate(20);

        return view('admin.coupons.show', compact('coupon', 'usages'));
    }

    public function create(): View
    {
        return view('admin.coupons.form', ['coupon' => new Coupon([
            'is_active' => true,
            'type' => 'percent',
            'currency' => 'BOTH',
            'max_uses_per_user' => 1,
            'applies_to' => 'all',
        ])]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $coupon = Coupon::create($this->attributes($request));

        Audit::record('coupon.created', 'coupon', $coupon->id, [], $coupon->only(['code', 'type', 'value', 'currency', 'is_active']));

        return redirect()->route('reddy.coupons.show', $coupon)->with('status', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $old = $coupon->only(['code', 'type', 'value', 'currency', 'is_active', 'max_uses']);

        $coupon->fill($this->attributes($request))->save();

        Audit::record('coupon.updated', 'coupon', $coupon->id, $old, $coupon->only(['code', 'type', 'value', 'currency', 'is_active', 'max_uses']));

        return redirect()->route('reddy.coupons.show', $coupon)->with('status', 'Coupon saved.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $old = $coupon->only(['code']);

        $coupon->delete();

        Audit::record('coupon.deleted', 'coupon', $coupon->id, $old, []);

        return redirect()->route('reddy.coupons.index')->with('status', 'Coupon deleted.');
    }

    protected function attributes(CouponRequest $request): array
    {
        $v = $request->validated();

        return [
            'code' => $v['code'],
            'type' => $v['type'],
            'value' => $v['value'],
            'currency' => $v['currency'],
            'min_order_amount' => $v['min_order_amount'] ?? null,
            'max_uses' => $v['max_uses'] ?? null,
            'max_uses_per_user' => $v['max_uses_per_user'],
            'starts_at' => $v['starts_at'] ?? null,
            'ends_at' => $v['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'applies_to' => $v['applies_to'],
            'applies_book_ids' => $v['applies_to'] === 'books' ? array_values($v['applies_book_ids'] ?? []) : null,
            'applies_category_ids' => $v['applies_to'] === 'categories' ? array_values($v['applies_category_ids'] ?? []) : null,
            'first_order_only' => $request->boolean('first_order_only'),
        ];
    }

    /**
     * Duplicate a coupon: same settings, fresh code (CODE-COPY),
     * zeroed usage counter. Audited as a creation.
     */
    public function duplicate(Coupon $coupon): RedirectResponse
    {
        $copy = $coupon->replicate(['uses_count']);
        $copy->code = $this->uniqueCopyCode($coupon->code);
        $copy->uses_count = 0;
        $copy->save();

        Audit::record('coupon.created', 'coupon', $copy->id, [], $copy->only(['code', 'type', 'value', 'currency', 'is_active']));

        return redirect()->route('reddy.coupons.edit', $copy)
            ->with('status', 'Coupon duplicated as '.$copy->code.'. Adjust the code if you like, then save.');
    }

    protected function uniqueCopyCode(string $code): string
    {
        $candidate = $code.'-COPY';
        $i = 2;

        while (Coupon::query()->where('code', $candidate)->exists()) {
            $candidate = $code.'-COPY-'.$i;
            $i++;
        }

        return $candidate;
    }

    /**
     * CSV export of every recorded usage of a coupon. Available to all
     * admins (Super Admin and Staff). Audited.
     */
    public function exportUsages(Coupon $coupon)
    {
        Audit::record('coupon.usages_exported', 'coupon', $coupon->id, [], ['code' => $coupon->code]);

        $usages = $coupon->usages()->with(['user', 'order'])->orderByDesc('used_at')->get();
        $filename = 'coupon-'.$coupon->code.'-usages-'.now()->format('Ymd-His').'.csv';

        return response()->stream(function () use ($usages) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['User', 'Email', 'Order ID', 'Discount amount', 'Currency', 'Used at (IST)']);

            foreach ($usages as $usage) {
                fputcsv($out, [
                    $usage->user?->name ?? '—',
                    $usage->user?->email ?? '—',
                    $usage->order?->order_number ?? '—',
                    $usage->order ? number_format((float) $usage->order->discount_total, 2, '.', '') : '0.00',
                    $usage->order?->currency ?? '—',
                    $usage->used_at ? $usage->used_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s') : '—',
                ]);
            }

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

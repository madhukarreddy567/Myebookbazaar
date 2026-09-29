<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ComboRequest;
use App\Models\Book;
use App\Models\Combo;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Phase 4: combo (bundle) management. Every change is audited.
 */
class ComboController extends Controller
{
    public function index(): View
    {
        $combos = Combo::query()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.combos.index', compact('combos'));
    }

    public function create(): View
    {
        return view('admin.combos.form', [
            'combo' => new Combo(['is_active' => true]),
            'books' => $this->bookOptions(),
        ]);
    }

    public function store(ComboRequest $request): RedirectResponse
    {
        $combo = Combo::create($this->attributes($request));

        Audit::record('combo.created', 'combo', $combo->id, [], $combo->only(['title', 'slug', 'is_active']));

        return redirect()->route('reddy.combos.index')->with('status', 'Combo created.');
    }

    public function edit(Combo $combo): View
    {
        return view('admin.combos.form', [
            'combo' => $combo,
            'books' => $this->bookOptions(),
        ]);
    }

    public function update(ComboRequest $request, Combo $combo): RedirectResponse
    {
        $old = $combo->only(['title', 'slug', 'is_active', 'combo_price_inr', 'combo_price_usd']);

        $combo->fill($this->attributes($request))->save();

        Audit::record('combo.updated', 'combo', $combo->id, $old, $combo->only(['title', 'slug', 'is_active', 'combo_price_inr', 'combo_price_usd']));

        return redirect()->route('reddy.combos.index')->with('status', 'Combo saved.');
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        $old = $combo->only(['title']);

        $combo->delete();

        Audit::record('combo.deleted', 'combo', $combo->id, $old, []);

        return redirect()->route('reddy.combos.index')->with('status', 'Combo deleted.');
    }

    protected function attributes(ComboRequest $request): array
    {
        $v = $request->validated();

        return [
            'title' => $v['title'],
            'slug' => $v['slug'],
            'description' => $v['description'] ?? null,
            'book_ids' => array_values($v['book_ids']),
            'combo_price_inr' => $v['combo_price_inr'],
            'combo_price_usd' => $v['combo_price_usd'],
            'sale_price_inr' => $v['sale_price_inr'] ?? null,
            'sale_price_usd' => $v['sale_price_usd'] ?? null,
            'sale_starts_at' => $v['sale_starts_at'] ?? null,
            'sale_ends_at' => $v['sale_ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $v['sort_order'] ?? 0,
        ];
    }

    protected function bookOptions()
    {
        return Book::query()
            ->orderBy('title')
            ->get(['id', 'title', 'is_active']);
    }
}

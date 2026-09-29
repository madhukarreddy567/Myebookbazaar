<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::query()->orderBy('slug')->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page(['is_active' => true])]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = Page::create($this->attributes($request));

        Audit::record('page.created', 'page', $page->id, [], $page->only(['slug', 'title', 'is_active']));

        return redirect()->route('reddy.pages.index')->with('status', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $old = $page->only(['slug', 'title', 'is_active']);
        $page->update($this->attributes($request));

        Audit::record('page.updated', 'page', $page->id, $old, $page->only(['slug', 'title', 'is_active']));

        return redirect()->route('reddy.pages.index')->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $old = $page->only(['slug', 'title']);
        $page->delete();

        Audit::record('page.deleted', 'page', $page->id, $old, []);

        return redirect()->route('reddy.pages.index')->with('status', 'Page deleted.');
    }

    protected function attributes(PageRequest $request): array
    {
        $validated = $request->validated();

        return [
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'content' => $validated['content'],
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

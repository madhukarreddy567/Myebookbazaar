<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount(['books', 'activeBooks'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($this->attributes($request));

        Audit::record('category.created', 'category', $category->id, [], $category->only(['name', 'slug', 'is_active']));

        return redirect()->route('reddy.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $old = $category->only(['name', 'slug', 'is_active']);
        $category->update($this->attributes($request));

        Audit::record('category.updated', 'category', $category->id, $old, $category->only(['name', 'slug', 'is_active']));

        return redirect()->route('reddy.categories.index')->with('status', 'Category saved.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->books()->exists()) {
            return back()->withErrors(['category' => 'This category still has books. Move or delete them first.']);
        }

        $old = $category->only(['name', 'slug']);
        $category->delete();

        Audit::record('category.deleted', 'category', $category->id, $old, []);

        return redirect()->route('reddy.categories.index')->with('status', 'Category deleted.');
    }

    protected function attributes(CategoryRequest $request): array
    {
        $validated = $request->validated();

        $slug = $validated['slug'] ?? null;
        if (! $slug) {
            $slug = Str::slug($validated['name']);
            $base = $slug;
            $i = 2;
            while (Category::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }
        }

        return [
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

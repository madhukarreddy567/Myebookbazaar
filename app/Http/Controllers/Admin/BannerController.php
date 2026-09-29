<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::query()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => new Banner(['is_active' => true])]);
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $banner = new Banner($this->attributes($request));
        $banner->image = $request->file('image')->store('banners', 'public');
        $banner->save();

        Audit::record('banner.created', 'banner', $banner->id, [], $banner->only(['title', 'is_active']));

        return redirect()->route('reddy.banners.index')->with('status', 'Banner created.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $old = $banner->only(['title', 'is_active']);

        $banner->fill($this->attributes($request));

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($banner->image);
            $banner->image = $request->file('image')->store('banners', 'public');
        }

        $banner->save();

        Audit::record('banner.updated', 'banner', $banner->id, $old, $banner->only(['title', 'is_active']));

        return redirect()->route('reddy.banners.index')->with('status', 'Banner saved.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $old = $banner->only(['title']);

        Storage::disk('public')->delete($banner->image);
        $banner->delete();

        Audit::record('banner.deleted', 'banner', $banner->id, $old, []);

        return redirect()->route('reddy.banners.index')->with('status', 'Banner deleted.');
    }

    protected function attributes(BannerRequest $request): array
    {
        $validated = $request->validated();

        return [
            'title' => $validated['title'],
            'link_url' => $validated['link_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

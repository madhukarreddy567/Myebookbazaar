<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Combo;
use Illuminate\Support\Facades\Cache;

/**
 * Serves /sitemap.xml (dynamic, cached) and /robots.txt.
 * Only public, indexable pages are listed: the storefront, static
 * content pages, active books and active combos. Checkout, account,
 * cart and admin pages are intentionally excluded.
 */
class SitemapController extends Controller
{
    public function sitemap()
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $urls = [];

            $static = [
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('books'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ];
            // Phase 8: only list combos/offers when their feature switches
            // are on — disabled features 404, and must not be indexed.
            if (settings_bool('features.combos', true)) {
                $static[] = ['loc' => route('combos'), 'priority' => '0.8', 'changefreq' => 'weekly'];
            }
            if (settings_bool('features.sales_offers', true)) {
                $static[] = ['loc' => route('offers'), 'priority' => '0.8', 'changefreq' => 'weekly'];
            }
            $static = array_merge($static, [
                ['loc' => route('about'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('faq'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('contact'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
                ['loc' => route('terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
                ['loc' => route('no-refund'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ]);
            foreach ($static as $s) {
                $urls[] = $s + ['lastmod' => null];
            }

            Book::where('is_active', true)->orderBy('updated_at', 'desc')
                ->get(['slug', 'updated_at'])
                ->each(function ($book) use (&$urls) {
                    $urls[] = [
                        'loc' => route('books.show', $book->slug),
                        'lastmod' => $book->updated_at?->toDateString(),
                        'priority' => '0.9',
                        'changefreq' => 'weekly',
                    ];
                });

            if (settings_bool('features.combos', true)) {
                Combo::where('is_active', true)->orderBy('updated_at', 'desc')
                    ->get(['slug', 'updated_at'])
                    ->each(function ($combo) use (&$urls) {
                        $urls[] = [
                            'loc' => route('combos.show', $combo->slug),
                            'lastmod' => $combo->updated_at?->toDateString(),
                            'priority' => '0.8',
                            'changefreq' => 'weekly',
                        ];
                    });
            }

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots()
    {
        $content = "User-agent: *\nDisallow:\n\nSitemap: ".route('sitemap')."\n";

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}

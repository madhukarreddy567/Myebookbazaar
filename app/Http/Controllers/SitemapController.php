<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Combo;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Serves /sitemap.xml (dynamic, cached) and /robots.txt.
 * Only public, indexable pages are listed: the storefront, static
 * content pages, active books and active combos. Checkout, account,
 * cart and admin pages are intentionally excluded.
 *
 * Fault isolation: every URL section is built inside its own
 * try/catch. A sitemap is crawler-facing infrastructure — one bad
 * record (or a transient failure in one section) must degrade to
 * "fewer URLs", never to a 500 that tells search engines the whole
 * site is broken. Failures are logged with context so they can be
 * fixed instead of silently ignored.
 */
class SitemapController extends Controller
{
    public function sitemap()
    {
        // NOTE: intentionally no Cache::remember and no Blade view here.
        // The sitemap is crawler-facing infrastructure: it must never 500
        // because of a cache-store hiccup or a view-compilation issue.
        // URL sections are fault-isolated (a bad record is skipped and
        // logged), and the XML is built with plain string concatenation
        // so there is no Blade compilation step to fail.
        $urls = [];

        $this->addStaticUrls($urls);
        $this->addBookUrls($urls);
        $this->addCategoryUrls($urls);
        $this->addComboUrls($urls);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $u) {
            $xml .= "    <url>\n"
                .'        <loc>'.e($u['loc'])."</loc>\n";
            if (! empty($u['lastmod'])) {
                $xml .= '        <lastmod>'.e($u['lastmod'])."</lastmod>\n";
            }
            $xml .= '        <changefreq>'.e($u['changefreq'])."</changefreq>\n"
                .'        <priority>'.e($u['priority'])."</priority>\n"
                ."    </url>\n";
        }

        $xml .= "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots()
    {
        $content = "User-agent: *\nDisallow:\n\nSitemap: ".route('sitemap')."\n";

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Run a sitemap section, logging (not throwing) on failure so one
     * bad section can never take down the whole sitemap.
     */
    protected function section(string $name, callable $build, array &$urls): void
    {
        try {
            $build($urls);
        } catch (Throwable $e) {
            Log::warning("sitemap: section '{$name}' failed and was skipped", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate a route URL, returning null (and logging) instead of
     * throwing when the route or its parameters are unusable — e.g. a
     * record with a missing slug.
     */
    protected function safeRoute(string $name, mixed $params = []): ?string
    {
        try {
            return route($name, $params);
        } catch (Throwable $e) {
            Log::warning("sitemap: could not generate route '{$name}'", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function addStaticUrls(array &$urls): void
    {
        $this->section('static', function (&$urls) {
            $static = [
                ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'daily'],
                ['route' => 'books', 'priority' => '0.9', 'changefreq' => 'daily'],
            ];
            // Phase 8: only list combos/offers when their feature switches
            // are on — disabled features 404, and must not be indexed.
            if (settings_bool('features.combos', true)) {
                $static[] = ['route' => 'combos', 'priority' => '0.8', 'changefreq' => 'weekly'];
            }
            if (settings_bool('features.sales_offers', true)) {
                $static[] = ['route' => 'offers', 'priority' => '0.8', 'changefreq' => 'weekly'];
            }
            $static = array_merge($static, [
                ['route' => 'about', 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['route' => 'faq', 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['route' => 'contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['route' => 'privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
                ['route' => 'terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
                ['route' => 'no-refund', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ]);

            foreach ($static as $s) {
                $loc = $this->safeRoute($s['route']);
                if ($loc !== null) {
                    $urls[] = [
                        'loc' => $loc,
                        'lastmod' => null,
                        'priority' => $s['priority'],
                        'changefreq' => $s['changefreq'],
                    ];
                }
            }
        }, $urls);
    }

    protected function addBookUrls(array &$urls): void
    {
        $this->section('books', function (&$urls) {
            Book::where('is_active', true)->orderBy('updated_at', 'desc')
                ->get(['slug', 'updated_at'])
                ->each(function ($book) use (&$urls) {
                    if (trim((string) $book->slug) === '') {
                        Log::warning('sitemap: book with empty slug skipped', ['book_id' => $book->id ?? null]);

                        return;
                    }
                    $loc = $this->safeRoute('books.show', $book->slug);
                    if ($loc !== null) {
                        $urls[] = [
                            'loc' => $loc,
                            'lastmod' => $book->updated_at?->toDateString(),
                            'priority' => '0.9',
                            'changefreq' => 'weekly',
                        ];
                    }
                });
        }, $urls);
    }

    protected function addCategoryUrls(array &$urls): void
    {
        $this->section('categories', function (&$urls) {
            // Category pages are filtered catalogue URLs (only active books
            // are listed there, so they are always indexable).
            Category::where('is_active', true)->orderBy('updated_at', 'desc')
                ->get(['slug', 'updated_at'])
                ->each(function ($category) use (&$urls) {
                    if (trim((string) $category->slug) === '') {
                        return;
                    }
                    $loc = $this->safeRoute('books', ['category' => $category->slug]);
                    if ($loc !== null) {
                        $urls[] = [
                            'loc' => $loc,
                            'lastmod' => $category->updated_at?->toDateString(),
                            'priority' => '0.7',
                            'changefreq' => 'weekly',
                        ];
                    }
                });
        }, $urls);
    }

    protected function addComboUrls(array &$urls): void
    {
        $this->section('combos', function (&$urls) {
            if (! settings_bool('features.combos', true)) {
                return;
            }
            Combo::where('is_active', true)->orderBy('updated_at', 'desc')
                ->get(['slug', 'updated_at'])
                ->each(function ($combo) use (&$urls) {
                    if (trim((string) $combo->slug) === '') {
                        return;
                    }
                    $loc = $this->safeRoute('combos.show', $combo->slug);
                    if ($loc !== null) {
                        $urls[] = [
                            'loc' => $loc,
                            'lastmod' => $combo->updated_at?->toDateString(),
                            'priority' => '0.8',
                            'changefreq' => 'weekly',
                        ];
                    }
                });
        }, $urls);
    }
}

<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Str;

/**
 * Automated SEO: meta titles/descriptions, Open Graph inputs, canonical
 * URLs and schema.org JSON-LD. All content is derived from real data —
 * no invented stats, reviews or prices.
 */
class SeoService
{
    public const DESCRIPTION_LIMIT = 155;

    /**
     * "{Title} by {Author} | MyEbookBazaar". Author omitted when unknown.
     */
    public function bookTitle(Book $book): string
    {
        $title = trim((string) $book->title);
        $author = trim((string) ($book->author ?? ''));

        return $author !== ''
            ? "{$title} by {$author} | MyEbookBazaar"
            : "{$title} | MyEbookBazaar";
    }

    /**
     * ~155-char meta description from short_description → description →
     * subtitle, cleanly truncated at a word boundary. Falls back to a
     * plain sentence when the book has no description.
     */
    public function bookDescription(Book $book): string
    {
        $source = $book->short_description
            ?: $book->description
            ?: $book->subtitle
            ?: '';

        $clean = trim(preg_replace('/\s+/', ' ', strip_tags((string) $source)));

        if ($clean === '') {
            return trim((string) $book->title).' — an ebook from MyEbookBazaar.';
        }

        return $this->truncateWords($clean, self::DESCRIPTION_LIMIT);
    }

    /**
     * Truncate at the last word boundary within $limit chars; appends an
     * ellipsis only when something was cut.
     */
    public function truncateWords(string $text, int $limit = self::DESCRIPTION_LIMIT): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > (int) ($limit * 0.5)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut).'…';
    }

    /**
     * schema.org Book JSON-LD for a book detail page. Price comes from the
     * visitor's own PriceService quote (INR or USD) — real pricing only.
     */
    public function bookJsonLd(Book $book, array $priceQuote, ?string $imageUrl = null): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Book',
            'name' => trim((string) $book->title),
            'url' => route('books.show', $book->slug),
            'description' => $this->bookDescription($book),
        ];

        $author = trim((string) ($book->author ?? ''));
        if ($author !== '') {
            $data['author'] = ['@type' => 'Person', 'name' => $author];
        }

        if ($imageUrl) {
            $data['image'] = $imageUrl;
        }

        if (trim((string) ($book->language ?? '')) !== '') {
            $data['inLanguage'] = trim((string) $book->language);
        }

        $data['offers'] = [
            '@type' => 'Offer',
            'price' => number_format((float) ($priceQuote['amount'] ?? 0), 2, '.', ''),
            'priceCurrency' => $priceQuote['currency'] ?? 'INR',
            'availability' => 'https://schema.org/InStock',
            'url' => route('books.show', $book->slug),
        ];

        // Genuine verified-purchase ratings only: aggregateRating is emitted
        // when the book carries a real summary (withRatingSummary) with at
        // least one visible review — never fabricated.
        $ratingCount = (int) ($book->ratings_count ?? 0);
        $ratingAverage = $book->rating_average !== null ? (float) $book->rating_average : null;
        if ($ratingCount > 0 && $ratingAverage !== null) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($ratingAverage, 1, '.', ''),
                'reviewCount' => $ratingCount,
                'bestRating' => 5,
            ];
        }

        return $data;
    }

    /**
     * BreadcrumbList JSON-LD. Items: [['name' => .., 'url' => ..|null], ...].
     */
    public function breadcrumbJsonLd(array $items): array
    {
        $elements = [];
        $position = 1;

        foreach ($items as $item) {
            $element = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $item['name'],
            ];
            if (! empty($item['url'])) {
                $element['item'] = $item['url'];
            }
            $elements[] = $element;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * Organization + WebSite JSON-LD for the home page. The SearchAction
     * points at the real catalogue search (?q=) — nothing invented.
     */
    public function organizationJsonLd(): array
    {
        $home = route('home');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $home.'#organization',
                    'name' => 'MyEbookBazaar',
                    'url' => $home,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => asset('images/stitch/logo.jpg'),
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $home.'#website',
                    'name' => 'MyEbookBazaar',
                    'url' => $home,
                    'publisher' => ['@id' => $home.'#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => route('books').'?q={query}',
                        ],
                        'query-input' => 'required name=query',
                    ],
                ],
            ],
        ];
    }

    /**
     * Encode structured data as a safe <script type="application/ld+json">
     * tag. HEX escaping blocks </script> breakouts from data.
     */
    public function script(array $data): string
    {
        $json = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        );

        return '<script type="application/ld+json">'.$json.'</script>';
    }
}

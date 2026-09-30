<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Ebook preview: lets visitors read the first N pages of a book.
 *
 * - Admin controls: preview_enabled toggle + preview_pages count per book.
 * - Pages are rendered server-side as images (no raw PDF exposed).
 * - Screenshot deterrents: watermark overlay, disabled right-click/selection,
 *   blur-on-tab-switch, print blocked. (True screenshot prevention is not
 *   possible on the web; these raise the bar significantly.)
 */
class PreviewController extends Controller
{
    /**
     * Show the preview reader for a book.
     */
    public function show(Request $request, string $slug)
    {
        $book = Book::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $book->preview_enabled) {
            abort(404, 'Preview not available for this book.');
        }

        $maxPages = max(1, min(50, (int) $book->preview_pages ?: 5));
        $totalPages = $this->getTotalPages($book);
        $pages = min($maxPages, $totalPages);

        return view('books.preview', [
            'book' => $book,
            'previewPages' => $pages,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * Serve a single preview page as an image.
     * Only serves pages within the allowed preview range.
     */
    public function page(Request $request, string $slug, int $page)
    {
        $book = Book::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $book->preview_enabled) {
            abort(404);
        }

        $maxPages = max(1, min(50, (int) $book->preview_pages ?: 5));

        if ($page < 1 || $page > $maxPages) {
            abort(404, 'Page not available in preview.');
        }

        $imagePath = $this->getPageImage($book, $page);

        if (! $imagePath || ! file_exists($imagePath)) {
            abort(404, 'Preview page not available.');
        }

        return response()->file($imagePath, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    protected function getTotalPages(Book $book): int
    {
        if ($book->pages) {
            return (int) $book->pages;
        }

        try {
            $path = $this->resolvePdfPath($book);
            if (! $path) {
                return 0;
            }
            $parser = new PdfParser();
            $pdf = $parser->parseFile($path);
            return count($pdf->getPages());
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function getPageImage(Book $book, int $page): ?string
    {
        $cacheDir = storage_path('app/preview-cache/' . $book->id);
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cached = $cacheDir . '/page-' . $page . '.png';
        if (file_exists($cached)) {
            return $cached;
        }

        $pdfPath = $this->resolvePdfPath($book);
        if (! $pdfPath) {
            return null;
        }

        try {
            // Use Imagick if available, otherwise try pdftoppm.
            if (extension_loaded('imagick')) {
                $imagick = new \Imagick();
                $imagick->setResolution(150, 150);
                $imagick->readImage($pdfPath . '[' . ($page - 1) . ']');
                $imagick->setImageFormat('png');
                $this->addWatermark($imagick);
                $imagick->writeImage($cached);
                $imagick->clear();
                return $cached;
            }

            // Fallback: pdftoppm (poppler-utils).
            $tmpPrefix = $cacheDir . '/tmp-' . $page;
            $cmd = sprintf(
                'pdftoppm -png -r 150 -f %d -l %d %s %s 2>/dev/null',
                $page,
                $page,
                escapeshellarg($pdfPath),
                escapeshellarg($tmpPrefix)
            );
            exec($cmd, $output, $code);
            $generated = $tmpPrefix . '-' . $page . '.png';
            // pdftoppm names files like prefix-01.png with zero padding
            $generatedPadded = sprintf('%s-%02d.png', $tmpPrefix, $page);
            if (file_exists($generatedPadded)) {
                rename($generatedPadded, $cached);
                return $cached;
            }
            if (file_exists($generated)) {
                rename($generated, $cached);
                return $cached;
            }
        } catch (\Throwable $e) {
            // Fall through to null.
        }

        return null;
    }

    protected function resolvePdfPath(Book $book): ?string
    {
        if (! $book->file_path) {
            return null;
        }

        // Try storage disk first, then absolute path.
        if (Storage::disk('private')->exists($book->file_path)) {
            return Storage::disk('private')->path($book->file_path);
        }

        if (file_exists($book->file_path)) {
            return $book->file_path;
        }

        return null;
    }

    /**
     * Add a diagonal watermark to deter screenshots.
     */
    protected function addWatermark(\Imagick $imagick): void
    {
        try {
            $draw = new \ImagickDraw();
            $draw->setFillColor(new \ImagickPixel('rgba(128,128,128,0.15)'));
            $draw->setFontSize(48);
            $draw->setGravity(\Imagick::GRAVITY_CENTER);
            $draw->rotate(-30);
            $imagick->annotateImage($draw, 0, 0, 0, 'PREVIEW — MyEbookBazaar');
        } catch (\Throwable $e) {
            // Watermark is best-effort.
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Support\Audit;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * Book catalogue for the admin.
     */
    public function index(Request $request): View
    {
        $books = Book::query()
            ->with('category')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->input('q');
                $query->where('title', 'like', "%{$q}%");
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->input('category_id')))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get();

        return view('admin.books.index', compact('books', 'categories'));
    }

    public function create(): View
    {
        $book = new Book(['language' => 'English', 'is_active' => true]);
        $categories = Category::query()->orderBy('name')->get();

        return view('admin.books.form', compact('book', 'categories'));
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $data = $this->attributes($request);
        $data['slug'] = $this->uniqueSlug($request->input('slug') ?: $request->input('title'));

        $book = new Book($data);
        $this->storeFiles($request, $book);
        $book->save();

        Audit::record('book.created', 'book', $book->id, [], $this->auditValues($book));

        return redirect()->route('reddy.books.index')->with('status', 'Book created.');
    }

    public function edit(Book $book): View
    {
        $categories = Category::query()->orderBy('name')->get();

        return view('admin.books.form', compact('book', 'categories'));
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $old = $this->auditValues($book);

        $data = $this->attributes($request);
        if ($request->filled('slug') && $request->input('slug') !== $book->slug) {
            $data['slug'] = $this->uniqueSlug($request->input('slug'), $book->id);
        }

        $book->fill($data);
        $this->storeFiles($request, $book);
        $book->save();

        Audit::record('book.updated', 'book', $book->id, $old, $this->auditValues($book));

        return redirect()->route('reddy.books.index')->with('status', 'Book saved.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $old = $this->auditValues($book);

        // Delete the ROW first: order_items.book_id is RESTRICT, so a
        // book that appears in any historical order refuses deletion.
        // The files must be deleted only after the row is gone —
        // deleting them first would leave the surviving row pointing
        // at files that no longer exist.
        try {
            $book->delete();
        } catch (QueryException $e) {
            if (! $this->isForeignKeyConstraintError($e)) {
                throw $e;
            }

            return redirect()->route('reddy.books.index')->withErrors([
                'book' => 'This book appears in historical orders and cannot be deleted (order history must stay intact). Deactivate it instead to remove it from the store.',
            ]);
        }

        $this->deleteFiles($book);

        Audit::record('book.deleted', 'book', $book->id, $old, []);

        return redirect()->route('reddy.books.index')->with('status', 'Book deleted.');
    }

    /**
     * True for a MySQL foreign-key violation on delete (1451: cannot
     * delete a parent row while children reference it). Any other
     * database error is real and must surface, not be mistaken for
     * the "in historical orders" case.
     */
    protected function isForeignKeyConstraintError(QueryException $e): bool
    {
        $info = $e->errorInfo ?? [];

        return ($info[1] ?? null) == 1451;
    }

    /**
     * Scalar attributes from the request (files handled separately).
     */
    protected function attributes(BookRequest $request): array
    {
        $validated = $request->validated();

        return [
            'title' => $validated['title'],
            'category_id' => $validated['category_id'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'author' => $validated['author'] ?? null,
            'edition' => $validated['edition'] ?? null,
            'pages' => $validated['pages'] ?? null,
            'language' => $validated['language'] ?? 'English',
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'price_inr' => $validated['price_inr'],
            'price_usd' => $validated['price_usd'],
            'sale_price_inr' => $validated['sale_price_inr'] ?? null,
            'sale_price_usd' => $validated['sale_price_usd'] ?? null,
            'sale_starts_at' => $validated['sale_starts_at'] ?? null,
            'sale_ends_at' => $validated['sale_ends_at'] ?? null,
            'isbn' => $validated['isbn'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }

    /**
     * Cover goes to the public disk (allowed public image); the PDF goes
     * to private storage and is never publicly reachable.
     */
    protected function storeFiles(BookRequest $request, Book $book): void
    {
        if ($request->boolean('remove_cover') && $book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
            $book->cover_image = null;
        }

        if ($request->hasFile('cover')) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $book->cover_image = $request->file('cover')->store('covers', 'public');
        }

        if ($request->boolean('remove_pdf') && $book->file_path) {
            Storage::disk('local')->delete($book->file_path);
            $book->file_path = null;
            $book->file_size = null;
            $book->original_filename = null;
        }

        if ($request->hasFile('pdf')) {
            if ($book->file_path) {
                Storage::disk('local')->delete($book->file_path);
            }
            $pdf = $request->file('pdf');
            $book->file_path = $pdf->store('ebooks', 'local');
            $book->file_size = $pdf->getSize();
            $book->original_filename = $pdf->getClientOriginalName();
        }
    }

    protected function deleteFiles(Book $book): void
    {
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }
        if ($book->file_path) {
            Storage::disk('local')->delete($book->file_path);
        }
    }

    protected function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'book';
        $slug = $base;
        $i = 2;

        while (Book::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    protected function auditValues(Book $book): array
    {
        return $book->only([
            'title', 'slug', 'category_id', 'price_inr', 'price_usd',
            'sale_price_inr', 'sale_price_usd', 'is_active', 'is_featured',
        ]) + ['has_file' => $book->hasFile()];
    }
}

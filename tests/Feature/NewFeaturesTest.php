<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use App\Services\PurchaseStatsService;
use App\Services\SkuService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NewFeaturesTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBook(array $overrides = []): Book
    {
        $category = Category::first() ?? Category::create([
            'name' => 'Fitness',
            'slug' => 'fitness',
        ]);

        return Book::create(array_merge([
            'category_id' => $category->id,
            'title' => 'Test Book ' . uniqid(),
            'author' => 'Test Author',
            'price_inr' => 149,
            'price_usd' => 4.99,
            'is_active' => true,
        ], $overrides));
    }

    /** @test */
    public function sku_is_auto_generated_on_creation()
    {
        $book = $this->makeBook(['edition' => 'Edition 1']);

        $this->assertNotEmpty($book->sku);
        // Format: FIT-001-E1 (category code, number, edition)
        $this->assertMatchesRegularExpression('/^[A-Z]{3}-\d{3}(-E\d+)?$/', $book->sku);
    }

    /** @test */
    public function sku_includes_edition_suffix()
    {
        $book = $this->makeBook(['edition' => 'Edition 2']);

        $this->assertStringEndsWith('-E2', $book->sku);
    }

    /** @test */
    public function sku_without_edition_has_no_suffix()
    {
        $book = $this->makeBook(['edition' => null]);

        $this->assertDoesNotMatchRegularExpression('/-E\d+$/', $book->sku);
    }

    /** @test */
    public function sku_numbers_increment_within_category()
    {
        $book1 = $this->makeBook();
        $book2 = $this->makeBook();

        $num1 = (int) substr($book1->sku, 4, 3);
        $num2 = (int) substr($book2->sku, 4, 3);

        $this->assertGreaterThan($num1, $num2);
    }

    /** @test */
    public function preview_is_enabled_by_default_with_5_pages()
    {
        $book = $this->makeBook();

        $this->assertTrue($book->preview_enabled);
        $this->assertEquals(5, $book->preview_pages);
    }

    /** @test */
    public function preview_route_returns_404_when_disabled()
    {
        $book = $this->makeBook(['preview_enabled' => false]);

        $response = $this->get(route('books.preview', $book->slug));

        $response->assertStatus(404);
    }

    /** @test */
    public function preview_page_beyond_limit_returns_404()
    {
        $book = $this->makeBook(['preview_pages' => 3]);

        $response = $this->get(route('books.preview.page', [
            'slug' => $book->slug,
            'page' => 10,
        ]));

        $response->assertStatus(404);
    }

    /** @test */
    public function popular_flag_defaults_to_false()
    {
        $book = $this->makeBook();

        $this->assertFalse($book->is_popular);
        $this->assertEquals(0, $book->popular_score);
    }

    /** @test */
    public function purchase_stats_returns_correct_structure()
    {
        $book = $this->makeBook();
        $stats = app(PurchaseStatsService::class)->forBook($book);

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('this_week', $stats);
        $this->assertArrayHasKey('this_month', $stats);
        $this->assertArrayHasKey('last_month', $stats);
    }

    /** @test */
    public function purchase_label_returns_null_for_no_sales()
    {
        $book = $this->makeBook();
        $label = app(PurchaseStatsService::class)->purchaseLabel($book);

        $this->assertNull($label);
    }
}

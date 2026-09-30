<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StitchReskinSmokeTest extends TestCase
{
    use DatabaseTransactions;
    /** @dataProvider pageProvider */
    public function test_page_renders(string $url, int $expected): void
    {
        $response = $this->get($url);
        $response->assertStatus($expected);
    }

    public static function pageProvider(): array
    {
        return [
            'login' => ['/login', 200],
            'register' => ['/register', 200],
            'forgot password' => ['/forgot-password', 200],
            'about' => ['/about', 200],
            'faq' => ['/faq', 200],
            'privacy' => ['/privacy-policy', 200],
            'terms' => ['/terms', 200],
            'no-refund' => ['/no-refund-policy', 200],
            '404 page' => ['/this-page-does-not-exist-xyz', 404],
        ];
    }

    public function test_login_contains_form_and_csrf(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('action="' . route('login') . '"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('_token', false);
        $response->assertSee('href="' . route('register') . '"', false);
    }

    public function test_register_contains_country_and_terms(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('name="country_code"', false);
        $response->assertSee('name="terms"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('href="' . route('login') . '"', false);
    }

    public function test_legal_pages_share_policy_shell(): void
    {
        foreach (['/privacy-policy' => 'Privacy Policy', '/terms' => 'Terms of Service', '/no-refund-policy' => 'No-Refund Policy'] as $url => $title) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertSee($title);
            $response->assertSee('Table of Contents');
            $response->assertSee('href="' . route('contact') . '"', false);
        }
    }

    public function test_error_page_renders_stitch_shell(): void
    {
        $response = $this->get('/this-page-does-not-exist-xyz');
        $response->assertStatus(404);
        $response->assertSee('Error 404');
        $response->assertSee('Quick Recovery Shelf');
        $response->assertSee('href="' . route('home') . '"', false);
    }

    public function test_preview_page_renders_reader_chrome(): void
    {
        $category = \App\Models\Category::first() ?? \App\Models\Category::create([
            'name' => 'Fitness',
            'slug' => 'fitness',
        ]);
        $book = \App\Models\Book::create([
            'category_id' => $category->id,
            'title' => 'Preview Smoke Book ' . uniqid(),
            'slug' => 'preview-smoke-book-' . uniqid(),
            'author' => 'Test Author',
            'price_inr' => 149,
            'price_usd' => 4.99,
            'is_active' => true,
            'pages' => 54,
            'preview_enabled' => true,
            'preview_pages' => 3,
        ]);

        $response = $this->get(route('books.preview', $book->slug));
        $response->assertStatus(200);
        $response->assertSee('preview-pages', false);
        $response->assertSee('preview-root', false);
        $response->assertSee('Official Preview');
        $response->assertSee('Page 1 of 3');
        $response->assertSee(route('books.preview.page', ['slug' => $book->slug, 'page' => 1]), false);
        $response->assertSee('Get the Full Book');
        // Deterrence JS hooks survive the reskin.
        $response->assertSee('contextmenu', false);
        $response->assertSee('visibilitychange', false);
        $response->assertSee('PrintScreen', false);

        $book->delete();
    }
}

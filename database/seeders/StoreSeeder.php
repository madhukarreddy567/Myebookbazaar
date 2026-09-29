<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Phase 2B: launch catalogue seed data. Idempotent — safe to re-run.
 *
 * NOTE (flagged per spec): the Phase 2B prompt says 55 pages for
 * "Fit at Home: Start (Edition 1)", but the approved real PDF was
 * verified at 54 pages. The seed uses 54 to match the real product.
 */
class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::updateOrCreate(
            ['slug' => 'fitness-diet'],
            [
                'name' => 'Fitness & Diet',
                'description' => 'Home workouts, simple nutrition and healthy-habit guides built for real Indian kitchens.',
                'sort_order' => 0,
                'is_active' => true,
            ]
        );

        Book::updateOrCreate(
            ['slug' => 'fit-at-home-start-edition-1'],
            [
                'category_id' => $category->id,
                'title' => 'Fit at Home: Start (Edition 1)',
                'subtitle' => 'The Simple Fitness & Diet Guide for Total Wellness',
                'short_description' => 'The simple Indian-kitchen fitness and diet guide: home workouts and a 30-day starter plan.',
                'description' => <<<'TEXT'
                    Fit at Home: Start is the no-gym, no-starvation way to begin your fitness journey.

                    Inside you get 10 chapters across 54 pages: simple home workouts that need no equipment, a full 30-day starter plan, and meal guidance built around everyday Indian food — dal, roti, rice, eggs and vegetables.

                    Everything is written in plain, simple language. No complicated science, no expensive supplements, no gym membership needed. Just 20 minutes a day at home.

                    What you get:
                    - 10 easy-to-follow chapters (54 pages)
                    - A complete 30-day starter workout plan
                    - Indian-kitchen meal guidance with real food examples
                    - Simple habit tips that fit a busy routine

                    This is Edition 1 of the Fit at Home series — the perfect starting point before Level Up and the Complete Edition.
                    TEXT,
                'author' => 'MyEbookBazaar',
                'edition' => '1',
                'pages' => 54,
                'language' => 'English',
                'price_inr' => 149,
                'price_usd' => 4.99,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 0,
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About MyEbookBazaar',
                'content' => <<<'TEXT'
                    MyEbookBazaar is an online bookstore selling short, practical ebooks on fitness, skills and money — written in simple language and priced honestly.

                    What we believe:

                    Good guidance should be short enough to finish and clear enough to act on. Advice should fit real life — Indian kitchens, home workouts, small budgets. Prices should be honest: rupees for India, dollars for the rest of the world.

                    What you get:

                    Every ebook is a PDF that works on any phone, tablet or computer. You pay once through secure Razorpay checkout and download instantly — no subscription, no waiting.
                    TEXT,
                'is_active' => true,
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'faq'],
            [
                'title' => 'Frequently asked questions',
                'content' => <<<'TEXT'
                    How do I pay?
                    Securely through Razorpay — UPI, credit and debit cards, and netbanking. Prices show in rupees for India and in dollars for other countries.

                    How do I get my ebook?
                    Right after payment you get a download link in your account library. The file is a standard PDF that works on any phone, tablet or computer.

                    Can I get a refund?
                    All sales are final because ebooks are digital products. Please read the book description carefully before you pay.

                    Do I need an account to browse?
                    No. You can browse every book without an account. You only sign in when you are ready to buy.

                    How many times can I download my ebook?
                    Every purchase includes up to 5 downloads, so you can keep a copy on your phone and your computer.
                    TEXT,
                'is_active' => true,
            ]
        );
    }
}

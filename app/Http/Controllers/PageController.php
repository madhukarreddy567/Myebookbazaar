<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerifiesTurnstile;
use App\Mail\ContactMessageMail;
use App\Models\Page;
use App\Services\HoneypotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PageController extends Controller
{
    use VerifiesTurnstile;
    /**
     * About page: admin-editable content, seeded default.
     */
    public function about(): View
    {
        $page = $this->pageOrFail('about', 'About us');

        return view('pages.about', compact('page'));
    }

    /**
     * FAQ page: admin-editable content, seeded default.
     *
     * Content format (documented in the admin pages editor): one
     * question-and-answer per blank-line-separated block; the first line
     * is the question, the rest is the answer.
     */
    public function faq(): View
    {
        $page = $this->pageOrFail('faq', 'Frequently asked questions');

        return view('pages.faq', [
            'page' => $page,
            'items' => $this->parseFaq($page->content),
        ]);
    }

    /**
     * Contact form. The whole page (and route) honours the contact_form
     * switch via the feature middleware.
     */
    public function contact(): View
    {
        return view('pages.contact');
    }

    public function contactSend(Request $request, HoneypotService $honeypot): RedirectResponse
    {
        // Phase 6: bots get the normal success message; nothing is sent.
        if ($honeypot->isBot($request)) {
            $honeypot->recordHit($request, 'contact');

            return back()->with('status', 'Thanks — your message is on its way. We reply within 2 working days.');
        }

        $this->verifyTurnstile($request, 'contact');

        $data = $request->validate([
            // name/subject land in mail headers via ContactMessageMail —
            // reject CR/LF (same header-injection class as GHSA-5vg9-5847-vvmq).
            'name' => ['required', 'string', 'max:100', 'not_regex:/[\r\n]/'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:150', 'not_regex:/[\r\n]/'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $to = (string) settings('site.support_email', config('mail.from.address'));

        Mail::to($to)->send(new ContactMessageMail(
            $data['name'],
            $data['email'],
            $data['subject'] ?: 'Website contact form',
            $data['message'],
        ));

        return back()->with('status', 'Thanks — your message is on its way. We reply within 2 working days.');
    }

    /**
     * Legal pages read from the Settings Center (Phase 2A legal tab).
     */
    public function privacy(): View
    {
        return $this->legal('legal.privacy_policy', 'Privacy policy', 'pages.privacy');
    }

    public function terms(): View
    {
        return $this->legal('legal.terms', 'Terms of service', 'pages.terms');
    }

    public function noRefund(): View
    {
        return $this->legal('legal.no_refund_policy', 'No-refund policy', 'pages.no-refund');
    }

    /**
     * Parse the admin-editable FAQ text into accordion items.
     */
    protected function parseFaq(string $content): array
    {
        $items = [];
        $blocks = preg_split('/\n\s*\n/', trim($content));

        foreach ($blocks as $block) {
            $lines = preg_split('/\r?\n/', trim($block), 2);
            if (count($lines) === 2 && trim($lines[0]) !== '' && trim($lines[1]) !== '') {
                $items[] = ['q' => trim($lines[0]), 'a' => trim($lines[1])];
            }
        }

        return $items;
    }

    protected function pageOrFail(string $slug, string $fallbackTitle): Page
    {
        $page = Page::query()->where('slug', $slug)->where('is_active', true)->first();

        if (! $page) {
            $page = new Page(['slug' => $slug, 'title' => $fallbackTitle, 'content' => '']);
        }

        return $page;
    }

    /**
     * Render the Settings Center legal text when the admin has saved it;
     * otherwise fall back to the designed static page.
     */
    protected function legal(string $settingKey, string $title, string $fallbackView): View
    {
        $content = trim((string) settings($settingKey, ''));

        if ($content === '') {
            return view($fallbackView);
        }

        return view('pages.legal', [
            'title' => $title,
            'content' => $content,
        ]);
    }
}

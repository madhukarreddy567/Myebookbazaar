<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Test email sent from the Settings Center (Phase 2A).
 */
class SettingsTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'MyEbookBazaar — SMTP test email',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.settings-test-text',
        );
    }
}

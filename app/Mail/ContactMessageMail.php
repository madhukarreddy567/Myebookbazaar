<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Phase 2B: contact-form message forwarded to the support email
 * configured in the Settings Center. Reply-To is the visitor.
 */
class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $visitorName,
        public string $visitorEmail,
        public string $subjectLine,
        public string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Website contact: '.$this->subjectLine,
            replyTo: [$this->visitorEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.contact-message-text',
            with: [
                'visitorName' => $this->visitorName,
                'visitorEmail' => $this->visitorEmail,
                'messageBody' => $this->messageBody,
            ],
        );
    }
}

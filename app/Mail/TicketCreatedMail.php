<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * New-ticket notification to the support inbox. Sent ONLY when mail is
 * actually configured (see the customer TicketController); the ticket
 * itself is created regardless — mail failure never blocks it.
 */
class TicketCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
    ) {}

    public function envelope(): Envelope
    {
        // Strip CR/LF the same way the contact form does: the subject
        // lands in mail headers (header-injection class GHSA-5vg9-5847-vvmq).
        $subject = 'New support ticket: '.preg_replace('/[\r\n]+/', ' ', $this->ticket->subject);

        return new Envelope(
            subject: Str::limit($subject, 120),
            replyTo: [$this->ticket->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.support.ticket-created-text',
            with: [
                'ticket' => $this->ticket->loadMissing(['user', 'order', 'replies']),
            ],
        );
    }
}

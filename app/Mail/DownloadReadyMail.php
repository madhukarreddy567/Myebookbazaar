<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DownloadReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ebooks are ready to download',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.orders.download-ready',
            text: 'emails.orders.download-ready-text',
            with: ['order' => $this->order],
        );
    }
}

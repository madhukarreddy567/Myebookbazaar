<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ebook order '.$this->order->order_number.' is confirmed',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.orders.confirmation',
            text: 'emails.orders.confirmation-text',
            with: ['order' => $this->order],
        );
    }
}

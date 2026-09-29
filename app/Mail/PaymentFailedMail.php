<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $reason = '') {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment failed for order '.$this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.orders.payment-failed',
            text: 'emails.orders.payment-failed-text',
            with: ['order' => $this->order, 'reason' => $this->reason],
        );
    }
}

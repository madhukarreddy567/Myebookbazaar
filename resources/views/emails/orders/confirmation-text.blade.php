Hi {{ $order->user->name }},

Thank you for your order {{ $order->order_number }}.

Your payment of {{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }} was successful.

Your ebooks:
@foreach($order->items as $item)
- {{ $item->title }}
@endforeach

Download them any time from My Library: {{ route('library') }}

Digital sales are final and non-refundable.

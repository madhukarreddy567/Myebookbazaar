Hi {{ $order->user->name }},

Thank you for your order {{ $order->order_number }}.

Your payment of {{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }} was successful.
@if((float) $order->gst_rate > 0)

Includes GST ({{ \App\Services\OrderService::gstRateLabel((float) $order->gst_rate) }}%): {{ app(\App\Services\PriceService::class)->format((float) $order->gst_amount, $order->currency) }}
@endif

Your ebooks:
@foreach($order->items as $item)
- {{ $item->title }}
@endforeach

Download them any time from My Library: {{ route('library') }}

Digital sales are final and non-refundable.

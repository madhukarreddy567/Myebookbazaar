<!DOCTYPE html>
<html><body style="font-family: sans-serif; color: #10262C; max-width: 600px; margin: 0 auto; padding: 24px;">
<h2 style="color: #0B3D4A;">Thank you for your order!</h2>
<p>Hi {{ $order->user->name }},</p>
<p>Your payment for order <strong>{{ $order->order_number }}</strong> was successful.</p>
<table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
@foreach($order->items as $item)
<tr style="border-bottom: 1px solid #D5E1E3;">
<td style="padding: 8px 0;">{{ $item->title }}</td>
<td style="padding: 8px 0; text-align: right;">{{ app(\App\Services\PriceService::class)->format((float) $item->line_total, $item->currency) }}</td>
</tr>
@endforeach
</table>
<p><strong>Total paid: {{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }}</strong></p>
@if((float) $order->gst_rate > 0)
<p style="color: #4E6870; font-size: 13px;">Includes GST ({{ \App\Services\OrderService::gstRateLabel((float) $order->gst_rate) }}%): {{ app(\App\Services\PriceService::class)->format((float) $order->gst_amount, $order->currency) }}</p>
@endif
<p>You can download your ebooks any time from <a href="{{ route('library') }}" style="color: #0F5566;">My Library</a>.</p>
<p style="color: #4E6870; font-size: 13px;">Digital sales are final and non-refundable.</p>
</body></html>

<!DOCTYPE html>
<html><body style="font-family: sans-serif; color: #10262C; max-width: 600px; margin: 0 auto; padding: 24px;">
<h2 style="color: #0B3D4A;">Payment failed</h2>
<p>Hi {{ $order->user->name }},</p>
<p>Your payment for order <strong>{{ $order->order_number }}</strong> did not go through.</p>
@if($reason)<p style="color: #4E6870;">{{ $reason }}</p>@endif
<p><strong>No money was taken for this order.</strong> You can try again:</p>
<p><a href="{{ route('cart.index') }}" style="background: #F28C0F; color: #0B3D4A; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: bold;">Back to cart</a></p>
</body></html>

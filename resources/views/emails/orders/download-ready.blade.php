<!DOCTYPE html>
<html><body style="font-family: sans-serif; color: #10262C; max-width: 600px; margin: 0 auto; padding: 24px;">
<h2 style="color: #0B3D4A;">Your ebooks are ready</h2>
<p>Hi {{ $order->user->name }},</p>
<p>Your ebooks from order <strong>{{ $order->order_number }}</strong> are ready to download:</p>
<ul>
@foreach($order->items as $item)
<li>{{ $item->title }}</li>
@endforeach
</ul>
<p><a href="{{ route('library') }}" style="background: #F28C0F; color: #0B3D4A; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: bold;">Go to My Library</a></p>
<p style="color: #4E6870; font-size: 13px;">Each ebook can be downloaded up to {{ (int) settings('downloads.limit', 5) }} times.</p>
</body></html>

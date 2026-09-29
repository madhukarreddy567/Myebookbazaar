Hi {{ $order->user->name }},

Your payment for order {{ $order->order_number }} did not go through.
@if($reason)
Reason: {{ $reason }}
@endif
No money was taken for this order. You can try again from your cart: {{ route('cart.index') }}

Hi {{ $order->user->name }},

Your ebooks from order {{ $order->order_number }} are ready to download:

@foreach($order->items as $item)
- {{ $item->title }}
@endforeach

Go to My Library: {{ route('library') }}

Each ebook can be downloaded up to {{ (int) settings('downloads.limit', 5) }} times.

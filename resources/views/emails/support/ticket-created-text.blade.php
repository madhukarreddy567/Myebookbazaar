A new support ticket was opened on MyEbookBazaar.

Ticket #{{ $ticket->id }} — {{ $ticket->statusLabel() }}
Subject: {{ $ticket->subject }}
Category: {{ $ticket->categoryLabel() }}
Customer: {{ $ticket->user->name }} <{{ $ticket->user->email }}>
@if($ticket->order)
Order: {{ $ticket->order->order_number }} ({{ $ticket->order->currency }} {{ number_format((float) $ticket->order->grand_total, 2) }}, {{ $ticket->order->statusLabel() }})
@endif
Opened: {{ $ticket->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST

Message:
{{ $ticket->replies->first()?->body }}

Reply in the admin panel: {{ route('reddy.tickets.show', $ticket) }}

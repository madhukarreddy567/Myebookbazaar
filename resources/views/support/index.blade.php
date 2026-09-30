@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Support — MyEbookBazaar')

@php
$badgeStyles = [
    'open' => 'bg-primary-fixed/60 text-on-primary-fixed-variant',
    'awaiting_customer' => 'bg-surface-container-high text-on-secondary-container',
    'awaiting_support' => 'bg-secondary-container/60 text-secondary',
    'closed' => 'bg-surface-container text-on-surface-variant',
];
@endphp

@section('content')
<div class="st-page w-full max-w-[1120px] mx-auto px-4 sm:px-6 py-8 sm:py-12">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6">
        <div class="space-y-1">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-container/10 text-primary-container font-label-sm text-label-sm tracking-wide uppercase font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-primary-container"></span>
                Help Desk
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight font-bold">Support Tickets</h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-xl">
                Questions about your order, downloads or account — we reply within 2 working days.
            </p>
        </div>
        <a href="{{ route('support.create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm self-start sm:self-auto">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>New ticket</span>
        </a>
    </div>

    @if($tickets->isEmpty())
        <div class="bg-surface-container-lowest rounded-xl p-10 sm:p-14 shadow-sm text-center">
            <div class="w-14 h-14 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[28px]">support_agent</span>
            </div>
            <h2 class="font-headline-md text-headline-md text-on-surface font-bold">No tickets yet</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2 max-w-md mx-auto">
                Open a ticket and our support team will get back to you.
            </p>
            <a href="{{ route('support.create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm mt-6">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Open a ticket
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($tickets as $ticket)
            <a href="{{ route('support.show', $ticket) }}" class="bg-surface-container-lowest p-5 rounded-DEFAULT border border-surface-container-high shadow-sm block hover:shadow-md transition-shadow">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-headline-sm text-headline-sm text-on-surface font-semibold truncate">{{ $ticket->subject }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">
                            Ticket #{{ $ticket->id }} · {{ $ticket->categoryLabel() }} ·
                            {{ $ticket->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} ·
                            {{ $ticket->replies_count }} {{ Str::plural('message', $ticket->replies_count) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-label-sm font-label-sm font-semibold {{ $badgeStyles[$ticket->status] ?? $badgeStyles['closed'] }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            {{ $ticket->statusLabel() }}
                        </span>
                        <span class="material-symbols-outlined text-on-surface-variant text-[20px]">chevron_right</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection

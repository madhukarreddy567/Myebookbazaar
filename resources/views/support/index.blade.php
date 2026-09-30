@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Support — MyEbookBazaar')

@php
$badgeStyles = [
    'open' => 'bg-[#FFF4E5] text-[#B45309]',
    'awaiting_customer' => 'bg-[#EAF3F5] text-[#0B3D4A]',
    'awaiting_support' => 'bg-[#FFF0D6] text-[#8A4B00]',
    'closed' => 'bg-[#EDF1F7] text-[#5C6B75]',
];
@endphp

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My account', 'url' => route('account')], ['label' => 'Support']]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mt-3">
        <h1 class="st-h text-[34px] md:text-[42px]">Support</h1>
        <a href="{{ route('support.create') }}" class="st-btn st-btn-teal !min-h-[48px]">
            <x-ui.icon name="plus" class="w-5 h-5" /> New ticket
        </a>
    </div>
    <p class="text-sm st-muted mt-1">Questions about your order, downloads or account — we reply within 2 working days.</p>

    @if(session('status'))
        <x-ui.alert type="success" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mt-6">
        @if($tickets->isEmpty())
            <div class="st-card st-empty">
                <x-ui.empty-state icon="mail" title="No tickets yet"
                    text="Open a ticket and our support team will get back to you."
                    :action-url="route('support.create')" action-label="Open a ticket" />
            </div>
        @else
            <div class="space-y-4">
                @foreach($tickets as $ticket)
                <a href="{{ route('support.show', $ticket) }}" class="st-card p-5 block hover:shadow-md transition-shadow">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-extrabold text-lg truncate">{{ $ticket->subject }}</p>
                            <p class="text-sm st-muted">
                                Ticket #{{ $ticket->id }} · {{ $ticket->categoryLabel() }} ·
                                {{ $ticket->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} ·
                                {{ $ticket->replies_count }} {{ Str::plural('message', $ticket->replies_count) }}
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider {{ $badgeStyles[$ticket->status] ?? $badgeStyles['closed'] }}">
                            {{ $ticket->statusLabel() }}
                        </span>
                    </div>
                </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
@endsection

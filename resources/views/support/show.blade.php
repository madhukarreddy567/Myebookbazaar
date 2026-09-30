@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Ticket #' . $ticket->id . ' — MyEbookBazaar')

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
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Support', 'url' => route('support.index')], ['label' => 'Ticket #' . $ticket->id]]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mt-3">
        <h1 class="st-h text-[28px] md:text-[36px]">{{ $ticket->subject }}</h1>
        <span class="inline-flex items-center rounded-full px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider {{ $badgeStyles[$ticket->status] ?? $badgeStyles['closed'] }}">
            {{ $ticket->statusLabel() }}
        </span>
    </div>
    <p class="text-sm st-muted mt-1">
        Ticket #{{ $ticket->id }} · {{ $ticket->categoryLabel() }} ·
        Opened {{ $ticket->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
        @if($ticket->order) · Order <span class="font-bold text-[#0B3D4A]">#{{ $ticket->order->order_number }}</span> @endif
    </p>

    @if(session('status'))
        <x-ui.alert type="success" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- Thread --}}
    <div class="mt-6 space-y-4">
        @foreach($ticket->replies as $reply)
        <div class="st-card p-5 {{ $reply->is_admin ? '!border-[#0B3D4A]' : '' }}">
            <div class="flex items-center justify-between gap-3 mb-2">
                <p class="font-extrabold {{ $reply->is_admin ? 'text-[#0B3D4A]' : '' }}">
                    @if($reply->is_admin)
                    <span class="inline-flex items-center gap-1.5"><x-ui.icon name="shield" class="w-4 h-4" /> Support team</span>
                    @else
                    You
                    @endif
                </p>
                <p class="text-xs st-muted">{{ $reply->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
            </div>
            <div class="text-[15px] leading-relaxed whitespace-pre-line">{{ $reply->body }}</div>
        </div>
        @endforeach
    </div>

    {{-- Reply form --}}
    <div class="st-card p-6 mt-6 max-w-2xl">
        @if($ticket->isClosed())
            <p class="text-sm st-muted">This ticket is closed. Replying will reopen it and our support team will follow up.</p>
        @endif
        <form method="POST" action="{{ route('support.reply', $ticket) }}" class="mt-3">
            @csrf
            <label class="st-label" for="body">Your reply</label>
            <textarea id="body" name="body" rows="4" maxlength="5000" required
                      class="st-input @error('body') !border-[#B3372F] @enderror"
                      placeholder="Type your message…">{{ old('body') }}</textarea>
            @error('body')<p class="text-sm font-semibold text-[#B3372F] mt-1">{{ $message }}</p>@enderror
            <button type="submit" class="st-btn st-btn-teal !min-h-[50px] !px-8 mt-4">
                <x-ui.icon name="arrow-right" class="w-5 h-5" /> Send reply
            </button>
        </form>
    </div>
</div>
@endsection

@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Ticket #' . $ticket->id . ' — MyEbookBazaar')

@php
$badgeStyles = [
    'open' => 'bg-primary-fixed/60 text-on-primary-fixed-variant',
    'awaiting_customer' => 'bg-surface-container-high text-on-secondary-container',
    'awaiting_support' => 'bg-secondary-container/60 text-secondary',
    'closed' => 'bg-surface-container text-on-surface-variant',
];
@endphp

@section('content')
<div class="st-page w-full max-w-[1040px] mx-auto px-4 sm:px-6 py-10 md:py-14">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 pb-6 border-b border-surface-variant/40">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-fixed/40 text-on-primary-fixed-variant font-label-sm text-label-sm mb-3">
                <span class="w-2 h-2 rounded-full bg-primary-container"></span>
                Ticket #{{ $ticket->id }}
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight break-words">{{ $ticket->subject }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">
                {{ $ticket->categoryLabel() }} · Opened {{ $ticket->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                @if($ticket->order)
                · Order <a href="{{ route('orders.show', $ticket->order) }}" class="font-semibold text-primary hover:underline">#{{ $ticket->order->order_number }}</a>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0 self-start sm:self-auto">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-label-sm font-label-sm font-semibold {{ $badgeStyles[$ticket->status] ?? $badgeStyles['closed'] }}">
                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                {{ $ticket->statusLabel() }}
            </span>
            <a href="{{ route('support.index') }}" class="inline-flex items-center gap-2 font-label-md text-label-md text-on-surface-variant hover:text-on-surface transition-colors py-2 px-1">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>All tickets</span>
            </a>
        </div>
    </div>

    {{-- Thread --}}
    <div class="space-y-4">
        @foreach($ticket->replies as $reply)
        <div class="p-5 sm:p-6 rounded-DEFAULT border shadow-sm
            @if($reply->is_admin) bg-secondary-container/20 border-secondary/30
            @else bg-surface-container-lowest border-surface-container-high @endif">
            <div class="flex items-center justify-between gap-3 mb-2">
                <p class="font-label-md text-label-md font-semibold text-on-surface flex items-center gap-1.5">
                    @if($reply->is_admin)
                    <span class="material-symbols-outlined text-[18px] text-secondary">support_agent</span>
                    <span>Support team</span>
                    @else
                    <span class="material-symbols-outlined text-[18px] text-on-surface-variant">person</span>
                    <span>You</span>
                    @endif
                </p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $reply->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
            </div>
            <div class="font-body-md text-body-md text-on-surface leading-relaxed whitespace-pre-line">{{ $reply->body }}</div>
        </div>
        @endforeach
    </div>

    {{-- Reply form --}}
    <div class="bg-surface-container-lowest p-6 sm:p-8 rounded-xl shadow-sm mt-6 max-w-2xl">
        @if($ticket->isClosed())
        <div class="flex items-start gap-2.5 p-4 rounded-DEFAULT bg-surface-container-low mb-4">
            <span class="material-symbols-outlined text-[20px] text-secondary mt-0.5">info</span>
            <p class="font-body-sm text-body-sm text-on-surface-variant">This ticket is closed. Replying will reopen it and our support team will follow up.</p>
        </div>
        @endif
        <form method="POST" action="{{ route('support.reply', $ticket) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-1.5 font-medium" for="body">Your reply</label>
                <textarea id="body" name="body" rows="4" maxlength="5000" required
                          class="w-full p-4 rounded-DEFAULT bg-surface-container-low/40 text-on-surface font-body-md text-body-md placeholder:text-on-surface-variant/40 focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container/40 transition-all resize-none @error('body') ring-2 ring-error/50 @enderror"
                          placeholder="Type your message…">{{ old('body') }}</textarea>
                @error('body')<p class="font-body-sm text-body-sm text-error font-medium mt-1.5" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 min-w-[180px] h-12 px-8 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold tracking-wide hover:bg-primary shadow-sm active:scale-[0.99] transition-all cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">send</span>
                <span>Send reply</span>
            </button>
        </form>
    </div>
</div>
@endsection

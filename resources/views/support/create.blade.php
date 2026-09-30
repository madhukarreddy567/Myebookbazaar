@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Open a support ticket — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Support', 'url' => route('support.index')], ['label' => 'New ticket']]" />

    <h1 class="st-h text-[34px] md:text-[42px] mt-3">Open a ticket</h1>
    <p class="text-sm st-muted mt-1">Tell us what's wrong — include your order number if it's about a purchase.</p>

    <div class="st-card p-6 md:p-8 mt-6 max-w-2xl">
        <form method="POST" action="{{ route('support.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="st-label" for="subject">Subject</label>
                <input id="subject" name="subject" type="text" class="st-input @error('subject') !border-[#B3372F] @enderror"
                       value="{{ old('subject') }}" maxlength="150" required placeholder="e.g. I can't download my ebook">
                @error('subject')<p class="text-sm font-semibold text-[#B3372F] mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="st-label" for="category">What's it about?</label>
                <select id="category" name="category" class="st-input @error('category') !border-[#B3372F] @enderror" required>
                    <option value="">Choose a topic…</option>
                    @foreach(\App\Models\Ticket::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')<p class="text-sm font-semibold text-[#B3372F] mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="st-label" for="order_id">Related order (optional)</label>
                <select id="order_id" name="order_id" class="st-input @error('order_id') !border-[#B3372F] @enderror">
                    <option value="">Not about an order</option>
                    @foreach($orders as $order)
                    <option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>
                        #{{ $order->order_number }} — {{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}
                    </option>
                    @endforeach
                </select>
                @error('order_id')<p class="text-sm font-semibold text-[#B3372F] mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="st-label" for="message">Message</label>
                <textarea id="message" name="message" rows="6" maxlength="5000" required
                          class="st-input @error('message') !border-[#B3372F] @enderror"
                          placeholder="Describe the problem in detail…">{{ old('message') }}</textarea>
                @error('message')<p class="text-sm font-semibold text-[#B3372F] mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="st-btn st-btn-teal !min-h-[50px] !px-8">
                    <x-ui.icon name="mail" class="w-5 h-5" /> Send ticket
                </button>
                <a href="{{ route('support.index') }}" class="text-sm font-bold text-[#0B3D4A] hover:underline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

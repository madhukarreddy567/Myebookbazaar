{{-- Status badge: text + color. Usage: <x-admin.badge status="paid" /> --}}
@props(['status' => ''])

@php
$map = [
    'paid' => 'badge-success', 'active' => 'badge-success', 'completed' => 'badge-success',
    'pending' => 'badge-warning', 'test' => 'badge-warning',
    'failed' => 'badge-danger', 'blocked' => 'badge-danger', 'disabled' => 'badge-danger',
    'refunded' => 'badge-muted', 'inactive' => 'badge-muted', 'draft' => 'badge-muted',
];
$class = $map[strtolower($status)] ?? 'badge-info';
$label = $slot->isNotEmpty() ? $slot : ucfirst($status);
@endphp

<span {{ $attributes->merge(['class' => "badge $class"]) }}><span class="dot"></span>{{ $label }}</span>

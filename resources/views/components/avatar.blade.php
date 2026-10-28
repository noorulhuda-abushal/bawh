@props(['user', 'size' => 'md'])

@php
    // 🎨 Every user gets their own color (chosen from their id) and the first letter of their name
    $colors = ['#f2a54a', '#4fc9b0', '#f49ac1', '#a78bfa', '#7cc4fa', '#fca5a5', '#86efac', '#fcd34d'];
    $color = $colors[$user->id % count($colors)];

    $sizes = [
        'sm' => 'h-7 w-7 text-xs',
        'md' => 'h-10 w-10 text-base',
        'lg' => 'h-20 w-20 text-4xl',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'avatar ' . $sizes[$size]]) }} style="background-color: {{ $color }}" aria-hidden="true">
    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
</span>

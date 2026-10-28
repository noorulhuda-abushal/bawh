@extends('Layout.app')

@section('title', __('Admin dashboard'))
@section('content')
    <div class="mx-auto max-w-5xl space-y-12 px-6 pt-10 pb-24">

        {{-- 🛡️ Header --}}
        <header>
            <p class="kicker reveal">🛡️ {{ __('Admin') }}</p>
            <h1 class="section-title reveal">{{ __('Admin dashboard') }}</h1>
            <p class="hero-sub reveal mt-2">{{ __('Look after Bawh: see the numbers, and remove anything harmful.') }}</p>
        </header>

        {{-- 📊 Numbers --}}
        <section class="admin-stats reveal">
            @foreach ([
                ['👥', 'Users', $stats['users']],
                ['✍️', 'Quotes', $stats['quotes']],
                ['🌙', 'Anonymous', $stats['anonymous']],
                ['💌', 'Shared letters', $stats['letters']],
                ['🤍', 'Reactions', $stats['reactions']],
            ] as [$icon, $label, $number])
                <div class="admin-stat">
                    <span class="text-2xl">{{ $icon }}</span>
                    <strong data-count-to="{{ $number }}">{{ $number }}</strong>
                    <span class="text-sm opacity-70">{{ __($label) }}</span>
                </div>
            @endforeach
        </section>

        {{-- 🚩 Reports --}}
        <section class="admin-box reveal">
            <h2 class="admin-title">🚩 {{ __('Reports') }} ({{ $reports->count() }})</h2>
            @forelse ($reports as $report)
                @php
                    $item = $report['item'];
                    $type = class_basename($item);
                    $deleteRoute = match ($type) {
                        'Quote' => route('delete_quote', $item),
                        'Letter' => route('delete_letter', $item),
                        default => route('delete_answer', $item),
                    };
                @endphp
                <div class="report-row">
                    <div class="flex-1">
                        <span class="badge">🚩 {{ $report['count'] }}</span>
                        <span class="ms-2 text-xs opacity-70">{{ ['Quote' => __('Quote'), 'Letter' => __('Letter'), 'Answer' => __('Answer to the question')][$type] }}</span>
                        <p class="mt-2" dir="auto">{{ \Illuminate\Support\Str::limit($item->content, 160) }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin_dismiss_reports', [$type, $item->id]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-btn">✓ {{ __('It\'s fine') }}</button>
                    </form>
                    <form method="POST" action="{{ $deleteRoute }}" data-confirm="{{ __('Delete it? This can\'t be undone.') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-btn danger">{{ __('Delete') }}</button>
                    </form>
                </div>
            @empty
                <p class="opacity-70">{{ __('No reports. Bawh is a safe place 🤍') }}</p>
            @endforelse
        </section>

        {{-- 💔 Feelings: one bar for each feeling --}}
        <section class="admin-box reveal">
            <h2 class="admin-title">💔 {{ __('Feelings on Bawh') }}</h2>
            @php $maxFeeling = max(1, $feelings->max() ?? 1); @endphp
            <div class="space-y-3">
                @foreach (\App\Models\Quote::MOODS as $key => $mood)
                    @php $count = $feelings[$key] ?? 0; @endphp
                    <div class="feeling-row">
                        <span class="w-28 shrink-0 text-sm font-bold">{{ $mood['emoji'] }} {{ __($mood['name']) }}</span>
                        <span class="feeling-track">
                            <span class="feeling-bar" style="--w: {{ round($count / $maxFeeling * 100) }}%; --mood: {{ $mood['color'] }}"></span>
                        </span>
                        <span class="w-8 text-end text-sm font-bold">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 👥 Users --}}
        <section class="admin-box reveal">
            <h2 class="admin-title">👥 {{ __('Users') }} ({{ $users->count() }})</h2>
            <ul class="divide-y divide-white/10">
                @foreach ($users as $user)
                    <li class="flex flex-wrap items-center gap-3 py-3">
                        <a href="{{ route('profile', $user) }}" class="flex flex-1 items-center gap-3 hover:underline">
                            <x-avatar :user="$user" size="sm" />
                            <span>
                                <span class="block font-bold">
                                    {{ $user->name }}
                                    @if ($user->is_admin) <span class="badge ms-1">🛡️ {{ __('Admin') }}</span> @endif
                                </span>
                                <span class="block text-xs opacity-60">{{ $user->email }}</span>
                            </span>
                        </a>
                        <span class="text-xs opacity-70">✍️ {{ $user->quotes_count }} · 👥 {{ $user->followers_count }} · {{ $user->created_at->translatedFormat('j M Y') }}</span>

                        @if ($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin_toggle_role', $user) }}">
                                @csrf
                                <button type="submit" class="admin-btn">
                                    {{ $user->is_admin ? __('Remove admin') : __('Make admin') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin_delete_user', $user) }}"
                                data-confirm="{{ __('Delete :name and everything they wrote? This can\'t be undone.', ['name' => $user->name]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn danger">{{ __('Delete') }}</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- ✍️ Latest quotes --}}
        <section class="space-y-4">
            <h2 class="admin-title reveal">✍️ {{ __('Latest quotes') }}</h2>
            <div class="quote-grid">
                @forelse ($quotes as $quote)
                    <x-quote-card :quote="$quote" />
                @empty
                    <p class="empty-box col-span-full">{{ __('No quotes yet. Be the first to share one!') }}</p>
                @endforelse
            </div>
        </section>

        {{-- 💌 Shared letters --}}
        <section class="space-y-4">
            <h2 class="admin-title reveal">💌 {{ __('Shared letters') }}</h2>
            <div class="letters-grid">
                @forelse ($letters as $letter)
                    <x-letter-card :letter="$letter" />
                @empty
                    <p class="empty-box col-span-full">{{ __('No letters shared yet. Yours could be the first.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

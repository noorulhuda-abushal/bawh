@extends('Layout.app')

@section('title', $user->name)
@section('content')
    <div class="mx-auto max-w-5xl space-y-10 px-6 pt-8 pb-24">

        <a href="{{ route('home') }}" class="reveal inline-block opacity-70 hover:opacity-100">{{ __('← Back to all quotes') }}</a>

        {{-- 👤 Profile header --}}
        <section class="profile-card reveal">
            {{-- 🎨 Their colored avatar with the first letter of the name --}}
            <x-avatar :user="$user" size="lg" class="ring-4" />

            <div class="flex-1">
                <h1 class="text-2xl font-bold">
                    {{ $user->name }}
                    @if ($isMe)
                        <span class="badge ms-2 align-middle">{{ __('You') }}</span>
                    @endif
                    @if ($user->is_admin)
                        <span class="badge ms-1 align-middle">🛡️ {{ __('Admin') }}</span>
                    @endif
                </h1>
                <p class="mt-1 text-sm opacity-70">{{ __('Joined :date', ['date' => $user->created_at->translatedFormat('F Y')]) }}</p>

                <div class="mt-4 flex flex-wrap gap-6">
                    <div>
                        <p class="text-2xl font-bold" id="followers-count">{{ $followersCount }}</p>
                        <p class="text-xs opacity-70">{{ __('Followers') }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ $followingCount }}</p>
                        <p class="text-xs opacity-70">{{ __('Following count') }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ $quotes->count() }}</p>
                        <p class="text-xs opacity-70">{{ $quotes->count() === 1 ? __('Quote') : __('Quotes') }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ $totalReactions }}</p>
                        <p class="text-xs opacity-70">{{ __('Reactions received') }}</p>
                    </div>
                    @if ($isMe)
                        <div>
                            <p class="text-2xl font-bold">{{ $likeMe->count() }}</p>
                            <p class="text-xs opacity-70">{{ __('Things like me') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 👥 Follow button (not on my own profile) --}}
            @if (! $isMe)
                @auth
                    <button type="button" id="follow-btn" data-url="{{ route('toggle_follow', $user) }}"
                        aria-pressed="{{ $iFollow ? 'true' : 'false' }}"
                        class="follow-btn {{ $iFollow ? 'is-following' : '' }}">
                        <span class="follow-label">{{ $iFollow ? __('Following ✓') : __('+ Follow') }}</span>
                    </button>
                @else
                    <a href="{{ route('login') }}" class="follow-btn">{{ __('+ Follow') }}</a>
                @endauth
            @endif
        </section>

        {{-- 🗓️ Feelings map + 🔥 streak + 🏅 badges --}}
        <section class="journey reveal">
            <div class="journey-head">
                <h2 class="admin-title">🗓️ {{ $isMe ? __('My feelings map') : __('Feelings map') }}</h2>
                <span class="streak-pill">🔥 {{ __('Writing streak: :count', ['count' => $streak]) }}</span>
            </div>
            <p class="mb-4 text-sm opacity-75">{{ __('The last 5 weeks. Every day takes the color of the feeling written most that day.') }}</p>

            <div class="mood-map" role="img" aria-label="{{ __('Feelings map') }}">
                @foreach ($moodMap as $day)
                    @php $info = $day['mood'] ? \App\Models\Quote::MOODS[$day['mood']] ?? null : null; @endphp
                    <span class="mood-day {{ $info ? 'has-mood' : '' }} {{ $day['date']->isToday() ? 'is-today' : '' }}"
                        style="--i: {{ $loop->index }}; @if ($info) --mood: {{ $info['color'] }} @endif"
                        title="{{ $day['date']->translatedFormat('j F') }}{{ $info ? ' · ' . $info['emoji'] . ' ' . __($info['name']) : '' }}">{{ $info['emoji'] ?? '' }}</span>
                @endforeach
            </div>

            @if (count($feelingCounts))
                @php $topMood = array_key_first($feelingCounts); $topInfo = \App\Models\Quote::MOODS[$topMood] ?? null; @endphp
                @if ($topInfo)
                    <p class="mt-4 font-bold">
                        {{ $isMe ? __('Your most common feeling:') : __('Most common feeling:') }}
                        <span class="mood-tag" style="background: {{ $topInfo['color'] }}">{{ $topInfo['emoji'] }} {{ __($topInfo['name']) }}</span>
                        <span class="text-sm opacity-70">({{ $feelingCounts[$topMood] }})</span>
                    </p>
                @endif
            @endif

            {{-- 🏅 Badges: bright = earned, faded = not yet --}}
            <h3 class="mt-6 mb-3 font-bold">🏅 {{ __('Badges') }}</h3>
            <div class="badges">
                @foreach ($badges as [$emoji, $name, $how, $earned])
                    <div class="badge-item {{ $earned ? 'is-earned' : '' }}" title="{{ __($how) }}" style="--i: {{ $loop->index }}">
                        <span class="badge-emoji">{{ $emoji }}</span>
                        <span class="text-sm font-bold">{{ __($name) }}</span>
                        <span class="text-xs opacity-70">{{ $earned ? '✓ ' . __('Earned') : __($how) }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Their quotes --}}
        <section class="space-y-4">
            <h2 class="section-title">
                {{ $isMe ? __('My quotes') : __(":name's quotes", ['name' => $user->name]) }}
            </h2>
            @if ($isMe)
                <p class="text-sm opacity-70">🌙 {{ __('Your anonymous quotes show here only for you.') }}</p>
            @endif

            <div class="quote-grid">
                @forelse ($quotes as $quote)
                    <x-quote-card :quote="$quote" :favorite-ids="$favoriteIds" :reacted="$reacted" />
                @empty
                    <p class="empty-box col-span-full">{{ __('No quotes shared yet.') }}</p>
                @endforelse
            </div>
        </section>

        {{-- 🪞 Things like me (only on my own profile) --}}
        @if ($isMe)
            <section class="space-y-4">
                <h2 class="section-title">🪞 {{ __('Things like me') }}</h2>
                <p class="text-sm opacity-70">{{ __('The words that felt like you. Only you can see this list.') }}</p>

                <div class="quote-grid">
                    @forelse ($likeMe as $quote)
                        <x-quote-card :quote="$quote" :favorite-ids="$favoriteIds" :reacted="$reacted" />
                    @empty
                        <p class="empty-box col-span-full">{{ __('Nothing here yet. Tap 🪞 on any quote that feels like you.') }}</p>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
@endsection

@props(['quote', 'favoriteIds' => [], 'reacted' => [], 'followingIds' => []])

@php
    $isMine = auth()->check() && $quote->user_id === auth()->id();
    // 🛡️ Admins can delete any quote (but only the owner can edit)
    $isAdmin = auth()->check() && auth()->user()->is_admin;
    $isFav = in_array($quote->id, $favoriteIds);
    // 👥 Written by someone I follow (anonymous quotes never count, so they stay secret)
    $isFollowing = ! $quote->is_anonymous && in_array($quote->user_id, $followingIds);
    $mood = $quote->moodInfo();
@endphp

{{-- 🎨 The card takes the color of its feeling (--mood), and glows softly --}}
<blockquote id="quote-{{ $quote->id }}" data-quote="{{ $quote->content }}" data-id="{{ $quote->id }}"
    @if ($isMine) data-mine @endif @if ($isFav) data-fav @endif @if ($isFollowing) data-following @endif
    style="--mood: {{ $mood['color'] }}"
    class="quote-card reveal scroll-mt-28 {{ session('new_quote') === $quote->id ? 'just-arrived is-visible' : '' }}">

    <div class="mb-3 flex items-center justify-between gap-2">
        <span class="mood-tag">{{ $mood['emoji'] }} {{ __($mood['name']) }}</span>
        <span class="flex gap-1">
            @if ($quote->is_anonymous && $isMine)
                <span class="mine-tag">🌙 {{ __('Anonymous') }}</span>
            @endif
            @if ($isMine)
                <span class="mine-tag">{{ __('You') }}</span>
            @endif
        </span>
    </div>

    {{-- dir="auto": an English quote stays left-to-right even on the Arabic site, and the opposite --}}
    <p class="quote-text" dir="auto">{{ $quote->quoted() }}</p>

    {{-- 📖 The book it comes from (click to open the book in the library) --}}
    @if ($quote->book)
        <a href="{{ route('book', $quote->book) }}" class="book-link" dir="auto">
            📖 {{ $quote->book->title }}@if ($quote->book->author)<span class="opacity-70"> — {{ $quote->book->author }}</span>@endif
            @if ($quote->book->verified)<span class="book-check" title="{{ __('The AI confirmed this book') }}">✓</span>@endif
        </a>
    @endif

    {{-- 🤍 🫂 ✨ Reactions: everyone sees the numbers, only logged-in users can react --}}
    <div class="reactions">
        @foreach (\App\Models\Quote::REACTIONS as $type => $reaction)
            @php
                $active = in_array($quote->id . ':' . $type, $reacted);
                $count = $quote->{$type . '_count'} ?? 0;
            @endphp
            @auth
                <button type="button" class="reaction-btn {{ $active ? 'is-active' : '' }}"
                    data-url="{{ route('toggle_reaction', [$quote, $type]) }}" aria-pressed="{{ $active ? 'true' : 'false' }}"
                    title="{{ __($reaction['name']) }}">
                    <span class="reaction-emoji">{{ $reaction['emoji'] }}</span>
                    <span class="reaction-name">{{ __($reaction['name']) }}</span>
                    <span class="reaction-count">{{ $count }}</span>
                </button>
            @else
                <a href="{{ route('login') }}" class="reaction-btn" title="{{ __('Log in to react') }}">
                    <span class="reaction-emoji">{{ $reaction['emoji'] }}</span>
                    <span class="reaction-name">{{ __($reaction['name']) }}</span>
                    <span class="reaction-count">{{ $count }}</span>
                </a>
            @endauth
        @endforeach
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        {{-- 👤 Writer: click to open their profile. 🌙 Anonymous quotes hide the name --}}
        <p class="flex items-center gap-1 text-sm">
            @if ($quote->is_anonymous)
                <span class="inline-flex items-center gap-2 font-bold">
                    <span class="avatar anon-avatar h-7 w-7 text-xs" aria-hidden="true">🌙</span>
                    {{ __('Anonymous bawh') }}
                </span>
            @else
                <a href="{{ route('profile', $quote->user) }}" class="author-link inline-flex items-center gap-2 font-bold">
                    <x-avatar :user="$quote->user" size="sm" />
                    {{ $isMine ? __('You') : $quote->user->name }}
                </a>
            @endif
            <span class="text-xs opacity-60">· {{ $quote->created_at->translatedFormat('j M Y') }}</span>
        </p>

        <div class="card-actions">
            {{-- 🪞 "This is like me" (logged-in users only) --}}
            @auth
                <button type="button" class="fav-btn {{ $isFav ? 'fav-active' : '' }}"
                    data-url="{{ route('toggle_favorite', $quote) }}" aria-pressed="{{ $isFav ? 'true' : 'false' }}"
                    data-tip="{{ $isFav ? __('Remove from things like me') : __('This is like me') }}">
                    🪞 <span class="fav-label">{{ __('Like me') }}</span>
                </button>
            @endauth

            {{-- 📸 Save the quote as a story picture (Snapchat / Instagram) --}}
            <button type="button" class="story-btn" data-tip="{{ __('Share as a story') }}" aria-label="{{ __('Share as a story') }}"
                data-text="{{ $quote->quoted() }}" data-mood="{{ $mood['emoji'] }} {{ __($mood['name']) }}"
                data-color="{{ $mood['color'] }}" data-author="{{ $quote->authorName() }}"
                data-book="{{ $quote->book ? $quote->book->title . ($quote->book->author ? ' — ' . $quote->book->author : '') : '' }}">
                {{-- a story ring (like Instagram / Snapchat stories) with a + inside --}}
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
                    <defs>
                        <linearGradient id="story-ring" x1="0" y1="1" x2="1" y2="0">
                            <stop offset="0" stop-color="#f9a14a" /><stop offset=".5" stop-color="#e1306c" /><stop offset="1" stop-color="#8a3ab9" />
                        </linearGradient>
                    </defs>
                    <circle cx="12" cy="12" r="9.5" fill="none" stroke="url(#story-ring)" stroke-width="2.4" stroke-dasharray="4.2 2" />
                    <path d="M12 8.2v7.6M8.2 12h7.6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
                </svg>
            </button>

            {{-- 🚩 Report harmful words to the admin (not on my own quotes) --}}
            @auth
                @unless ($isMine)
                    <button type="button" class="report-btn" data-url="{{ route('report', ['quote', $quote->id]) }}"
                        data-tip="{{ __('Report') }}" aria-label="{{ __('Report') }}">🚩</button>
                @endunless
            @endauth

            {{-- 📋 Everyone can copy --}}
            <button type="button" class="copy-btn" aria-label="{{ __('Copy quote') }}" data-tip="{{ __('Copy') }}" data-done="{{ __('Copied ✓') }}">⧉</button>

            {{-- ✎ ⌫ Only the owner can edit and delete --}}
            @if ($isMine)
                <a href="{{ route('home', ['edit' => $quote->id]) }}#quote-{{ $quote->id }}" class="icon-btn" aria-label="{{ __('Edit') }}" data-tip="{{ __('Edit') }}">✎</a>
            @endif

            @if ($isMine || $isAdmin)
                <form method="POST" action="{{ route('delete_quote', $quote) }}"
                    data-confirm="{{ __("Delete this quote? This can't be undone.") }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="icon-btn" aria-label="{{ __('Delete') }}"
                        data-tip="{{ $isMine ? __('Delete') : __('🛡️ Delete (admin)') }}">{{ $isMine ? '⌫' : '🛡️' }}</button>
                </form>
            @endif
        </div>
    </div>
</blockquote>

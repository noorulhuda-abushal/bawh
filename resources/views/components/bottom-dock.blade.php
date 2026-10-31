@props(['page' => ''])

{{-- 🧭 The floating dock at the bottom: Write · Library · 🏠 Home (in the middle) · Letters · Me --}}
@php
    $isHome = request()->routeIs('home');
    $isMe = auth()->check() && request()->routeIs('profile') && request()->route('user')?->id === auth()->id();
@endphp
<nav class="dock" aria-label="{{ __('Main menu') }}">
    {{-- ✍️ Write: on the home page it opens the share box; on other pages it goes home and opens it --}}
    @auth
        @if ($isHome)
            <button type="button" popovertarget="quote-popup" class="dock-item">
                <span class="dock-icon">✍️</span><span>{{ __('Write') }}</span>
            </button>
        @else
            <a href="{{ route('home', ['write' => 1]) }}" class="dock-item">
                <span class="dock-icon">✍️</span><span>{{ __('Write') }}</span>
            </a>
        @endif
    @else
        <a href="{{ route('login') }}" class="dock-item">
            <span class="dock-icon">✍️</span><span>{{ __('Write') }}</span>
        </a>
    @endauth

    <a href="{{ route('library') }}" class="dock-item {{ $page === 'Library' ? 'is-active' : '' }}">
        <span class="dock-icon">📚</span><span>{{ __('Library') }}</span>
    </a>

    {{-- 🏠 Home in the middle (the page you are on rises in an orange circle) --}}
    <a href="{{ route('home') }}" class="dock-item dock-home {{ $isHome ? 'is-active' : '' }}" aria-label="{{ __('Home') }}">
        <span class="dock-icon">🏠</span><span>{{ __('Home') }}</span>
    </a>

    <a href="{{ route('letters') }}" class="dock-item {{ $page === 'Letters' ? 'is-active' : '' }}">
        <span class="dock-icon">💌</span><span>{{ __('Letters') }}</span>
    </a>

    {{-- 👤 My profile (guests: log in) --}}
    @auth
        <a href="{{ route('profile', auth()->user()) }}" class="dock-item dock-me {{ $isMe ? 'is-active' : '' }}">
            <span class="dock-icon">👤</span><span>{{ __('Me') }}</span>
        </a>
    @else
        <a href="{{ route('login') }}" class="dock-item">
            <span class="dock-icon">👤</span><span>{{ __('Log In') }}</span>
        </a>
    @endauth
</nav>

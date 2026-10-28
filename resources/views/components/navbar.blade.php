@props(['page' => ''])

<nav class="site-nav">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2">

        {{-- Start side (left in English, right in Arabic): buttons and user info --}}
        <ul class="flex items-center gap-2 sm:gap-4">
            @if (($page ?? '') === 'Sign Up')
                <li><a href="{{ route('login') }}" class="btn-primary">{{ __('Log In') }}</a></li>
            @elseif(($page ?? '') === 'Log In')
                <li><a href="{{ route('signup') }}" class="btn-primary">{{ __('Sign Up') }}</a></li>
            @else
                @auth
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-primary">{{ __('Log Out') }}</button>
                        </form>
                    </li>
                    {{-- 👤 Click the picture or name to open my profile --}}
                    <li>
                        <a href="{{ route('profile', auth()->user()) }}" class="flex items-center gap-3" title="{{ __('My profile') }}">
                            <x-avatar :user="auth()->user()" class="transition hover:scale-105" />
                            <span class="hidden hover:text-tuwaiq-orange sm:block">{{ auth()->user()->name }}</span>
                        </a>
                    </li>
                @endauth

                @guest
                    <li><a href="{{ route('login') }}" class="btn-primary">{{ __('Log In') }}</a></li>
                    <li class="hidden sm:block">
                        <a href="{{ route('signup') }}" class="hover:text-tuwaiq-orange">{{ __('Sign Up') }}</a>
                    </li>
                @endguest
            @endif
        </ul>

        {{-- Center: site name --}}
        {{-- 🏷️ Logo (the same in Arabic and English): بوح + orange brush line + BAWH --}}
        <a href="{{ route('home') }}" class="logo" aria-label="بوح Bawh - {{ __('Home') }}" dir="rtl">
            <span class="logo-ar">بوح</span>
            <svg class="logo-swoosh" viewBox="0 0 110 14" aria-hidden="true">
                <path d="M4 9 C30 2 70 2 106 7" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" />
            </svg>
            <span class="logo-en">BAWH</span>
        </a>

        {{-- End side: letters + settings + Tuwaiq logo --}}
        <div class="flex items-center gap-3">
            {{-- 🛡️ Admin dashboard (admins only) --}}
            @if (auth()->user()?->is_admin)
                <a href="{{ route('admin') }}" class="nav-link {{ ($page ?? '') === 'Admin' ? 'is-active' : '' }}" title="{{ __('Admin dashboard') }}">
                    <span aria-hidden="true">🛡️</span>
                    <span class="hidden lg:inline">{{ __('Dashboard') }}</span>
                </a>
            @endif

            {{-- 💌 Letters that won't be sent --}}
            <a href="{{ route('letters') }}" class="nav-link {{ ($page ?? '') === 'Letters' ? 'is-active' : '' }}"
                title="{{ __('A letter that won\'t be sent') }}">
                <span aria-hidden="true">💌</span>
                <span class="hidden lg:inline">{{ __('Letters') }}</span>
            </a>
            {{-- 🎧 Sounds --}}
            <button type="button" popovertarget="sounds-panel" aria-label="{{ __('Sounds to write with') }}" title="{{ __('Sounds to write with') }}"
                class="settings-btn sounds-btn">🎧</button>
            <button type="button" popovertarget="settings-panel" aria-label="{{ __('Settings') }}" title="{{ __('Settings') }}"
                class="settings-btn">⚙️</button>
            {{-- Tuwaiq logo without a background: white for the night theme, dark for the dawn theme --}}
            <img src="{{ asset('images/logo.png') }}" class="tuwaiq-logo logo-for-night hidden h-12 w-auto sm:block md:h-14" alt="Tuwaiq Club">
            <img src="{{ asset('images/logo-dark.png') }}" class="tuwaiq-logo logo-for-dawn hidden h-12 w-auto md:h-14" alt="Tuwaiq Club">
        </div>
    </div>
</nav>

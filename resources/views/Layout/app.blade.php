{{-- 🌐 lang + dir: Arabic pages go right-to-left --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Security token that JavaScript sends with requests (reactions, things like me, mirror, AI) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#160b33">

    <title>@hasSection('title')@yield('title') · @endif{{ __('Bawh') }}</title>

    {{-- 🌙 Tab icon: an orange crescent moon on the night sky (public/favicon.ico).
         "?v=bawh" makes the browser forget the old Laravel icon it saved --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=bawh" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=bawh">

    {{-- ⚙️ Apply the saved settings (theme, text size, motion) before the page shows, so it doesn't flash --}}
    <script>
        // "js" = JavaScript works, so things can fade in while scrolling
        document.documentElement.classList.add('js');
        try {
            const s = JSON.parse(localStorage.getItem('quoteit-settings')) || {};
            // 🕰️ "auto": dawn colors in the day (6 AM – 6 PM), night colors at night
            if (s.theme === 'auto') {
                const hour = new Date().getHours();
                document.documentElement.dataset.theme = hour >= 6 && hour < 18 ? 'light' : 'dark';
            } else if (s.theme) {
                document.documentElement.dataset.theme = s.theme;
            }
            if (s.size) document.documentElement.dataset.size = s.size;
            if (s.motion) document.documentElement.dataset.motion = s.motion;
        } catch (e) {}
    </script>

    {{-- 🌐 Words that JavaScript shows (toasts, greeting…) in the chosen language --}}
    @php
        $jsWords = [
            'Good morning', 'Good evening',
            'Copied to clipboard 📋', "Couldn't copy, try again",
            'Something went wrong, try again', 'Settings reset', 'Write at least 3 characters first ✍️',
            '✨ Thinking…', '✨ Improve with AI',
            'Give me another thought', 'Opening…', 'Reflecting…', '🪞 Reflect',
            'Write at least 2 letters ✍️', 'AM', 'PM', "It's the hour of night thoughts 🌌",
            'Added to things like me 🪞', 'Removed from things like me', 'This is like me', 'Remove from things like me',
            'Log in to react', 'Write about this feeling', 'Letting it go…', 'It is',
            'Following ✓', '+ Follow', 'It is gone… lighter now',
            'Saved! Add it to your story 📸', 'Making your story…', 'Your words, without your name 🌙', 'Bawh · A space for what we could not say',
            'Thank you, the report reached the admin 🤍', 'Sound on 🎧', 'Sound off', 'Saved ✓', 'Reported ✓', 'You are now following :name 👥', 'You unfollowed :name',
        ];
        $translations = [];
        foreach ($jsWords as $word) {
            $translations[$word] = __($word);
        }
    @endphp
    <script>
        window.i18n = @json($translations);
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="sky">
    {{-- ✨ Twinkling stars (made by app.js) --}}
    <div id="stars" class="stars" aria-hidden="true"></div>

    {{-- 🌅 Dawn scene (only shows in the Dawn theme): a warm sun, slow clouds,
         birds, and the night's little letters floating away with the morning breeze --}}
    <div class="dawn-scene" aria-hidden="true">
        <span class="dawn-glow"></span>
        <span class="cloud c1"></span>
        <span class="cloud c2"></span>
        <span class="cloud c3"></span>
        <span class="cloud c4"></span>
        @foreach ([['12%', '0s', '1'], ['16%', '-9s', '.7'], ['22%', '-17s', '.85']] as [$top, $delay, $size])
            <svg class="bird" style="top: {{ $top }}; animation-delay: {{ $delay }}; --s: {{ $size }}" viewBox="0 0 40 16">
                <path d="M2 10 Q11 1 20 9 Q29 1 38 10" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
            </svg>
        @endforeach
        @foreach ([['8%', '0s'], ['27%', '-6s'], ['48%', '-13s'], ['69%', '-3s'], ['86%', '-10s']] as [$left, $delay])
            <span class="dawn-note" style="left: {{ $left }}; animation-delay: {{ $delay }}"></span>
        @endforeach
    </div>

    {{-- 📏 Scroll progress line --}}
    <div class="scroll-progress" aria-hidden="true"><span id="scroll-progress"></span></div>

    <x-navbar :page="$page ?? ''" />

    <main>
        {{-- 💙 Shown after someone writes words about hurting themselves --}}
        @if (session('support'))
            <div class="mx-auto max-w-3xl px-6 pt-6">
                <x-support-box />
            </div>
        @endif

        @yield('content')
    </main>

    {{-- 🎧 Night sounds (rain / waves), made by app.js, no sound files needed --}}
    <div id="sounds-panel" popover class="sounds-panel">
        <p class="mb-3 font-bold">🎧 {{ __('Sounds to write with') }}</p>
        <div class="grid grid-cols-3 gap-2">
            <button type="button" class="sound-option" data-sound="rain">🌧️<span>{{ __('Rain') }}</span></button>
            <button type="button" class="sound-option" data-sound="waves">🌊<span>{{ __('Waves') }}</span></button>
            <button type="button" class="sound-option" data-sound="off">🔇<span>{{ __('Off') }}</span></button>
        </div>
    </div>

    {{-- ⬆️ Back to top (shows after scrolling down) --}}
    <button type="button" id="to-top" class="to-top" aria-label="{{ __('Back to top') }}" hidden>↑</button>

    {{-- ⚙️ Settings panel (opens from the gear button in the navbar) --}}
    <div id="settings-panel" popover class="panel">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-bold">{{ __('⚙️ Settings') }}</h2>
            <button type="button" popovertarget="settings-panel" popovertargetaction="hide"
                aria-label="{{ __('Close') }}" class="close-btn">✕</button>
        </div>

        {{-- 🌐 Language: saved on the server (session), so it uses links --}}
        <p class="setting-title">{{ __('Language') }}</p>
        <div class="setting-group">
            <a href="{{ route('lang', 'ar') }}" class="setting-option text-center {{ app()->getLocale() === 'ar' ? 'is-active' : '' }}">العربية</a>
            <a href="{{ route('lang', 'en') }}" class="setting-option text-center {{ app()->getLocale() === 'en' ? 'is-active' : '' }}">English</a>
        </div>

        <p class="setting-title">{{ __('Appearance') }}</p>
        <div class="setting-group">
            <button type="button" data-setting="theme" data-value="dark" class="setting-option">{{ __('🌙 Night') }}</button>
            <button type="button" data-setting="theme" data-value="light" class="setting-option">{{ __('🌅 Dawn') }}</button>
            <button type="button" data-setting="theme" data-value="auto" class="setting-option">{{ __('🕰️ Auto') }}</button>
        </div>
        <p class="-mt-1 text-xs opacity-60">{{ __('Auto: dawn in the day, night after sunset.') }}</p>

        <p class="setting-title">{{ __('Text size') }}</p>
        <div class="setting-group">
            <button type="button" data-setting="size" data-value="sm" class="setting-option text-sm">A</button>
            <button type="button" data-setting="size" data-value="md" class="setting-option text-base">A</button>
            <button type="button" data-setting="size" data-value="lg" class="setting-option text-lg">A</button>
            <button type="button" data-setting="size" data-value="xl" class="setting-option text-xl">A</button>
        </div>

        <p class="setting-title">{{ __('Motion') }}</p>
        <div class="setting-group">
            <button type="button" data-setting="motion" data-value="full" class="setting-option">{{ __('✨ Animations on') }}</button>
            <button type="button" data-setting="motion" data-value="reduce" class="setting-option">{{ __('🧘 Reduce motion') }}</button>
        </div>

        <p class="setting-title">{{ __('Shortcuts') }}</p>
        <ul class="space-y-1 text-sm opacity-80">
            <li><kbd>N</kbd> {{ __('Write a new quote') }}</li>
            <li><kbd>/</kbd> {{ __('Search') }}</li>
            <li><kbd>Ctrl</kbd> + <kbd>Enter</kbd> {{ __('Send') }}</li>
            <li><kbd>Esc</kbd> {{ __('Close') }}</li>
        </ul>

        <button type="button" id="reset-settings" class="mt-6 w-full text-sm opacity-60 hover:underline">
            {{ __('Reset to default') }}
        </button>
    </div>

    {{-- 🔔 Toast messages (data-flash = message from the controller, data-celebrate = first quote 🎉) --}}
    <div id="toast-area" aria-live="polite" data-flash="{{ session('toast') }}"
        @if (session('celebrate')) data-celebrate @endif
        class="pointer-events-none fixed bottom-28 left-1/2 z-[60] flex -translate-x-1/2 flex-col items-center gap-2"></div>

</body>

</html>

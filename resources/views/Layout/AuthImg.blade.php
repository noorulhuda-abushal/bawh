{{-- Layout for Log In / Sign Up: the form on one side, floating feeling notes on the other --}}
<div class="mx-auto grid max-w-5xl grid-cols-1 items-center gap-10 px-6 py-10 md:grid-cols-2 md:py-16">

    @yield('form')

    {{-- 🎨 Decoration: three floating notes in the mood colors --}}
    <div class="auth-notes hidden md:block" aria-hidden="true">
        <div class="auth-note" style="--mood: #ffe08a; --r: 5deg; top: 0; inset-inline-end: 10%">
            <span class="mood-tag">✨ {{ __('Hope') }}</span>
            <p>{{ __('Every ending is a new beginning.') }}</p>
        </div>
        <div class="auth-note" style="--mood: #cdb8ff; --r: -6deg; top: 150px; inset-inline-start: 0">
            <span class="mood-tag">🌙 {{ __('Nostalgia') }}</span>
            <p>{{ __('Some words stay with us forever.') }}</p>
        </div>
        <div class="auth-note" style="--mood: #ffc2d8; --r: 3deg; top: 290px; inset-inline-end: 4%">
            <span class="mood-tag">🤍 {{ __('Love') }}</span>
            <p>{{ __('Say it, someone needs to hear it.') }}</p>
        </div>
    </div>
</div>

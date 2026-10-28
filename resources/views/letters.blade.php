@extends('Layout.app')

@section('title', __('A letter that won\'t be sent'))
@section('content')
    <div class="mx-auto max-w-4xl space-y-14 px-6 pt-10 pb-24">

        {{-- 💌 Header --}}
        <header class="text-center">
            <p class="kicker reveal">{{ __('A letter that won\'t be sent') }} 💌</p>
            <h1 class="section-title reveal">{{ __('Who do you wish you could say something to, but couldn\'t?') }}</h1>
            <p class="hero-sub reveal mx-auto mt-2">{{ __('Write it here. Keep it, let it go, or share it without your name.') }}</p>
        </header>

        {{-- ✍️ The letter paper --}}
        @auth
            <form method="POST" action="{{ route('store_letter') }}" id="letter-form" class="letter-paper reveal">
                @csrf

                @if ($errors->any())
                    <div class="mb-4 rounded-2xl bg-red-100 p-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                {{-- To whom? --}}
                <fieldset>
                    <legend class="mb-2 font-bold">{{ __('To:') }}</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\Letter::RECIPIENTS as $key => $recipient)
                            <label class="mood-option" style="--mood: #f2a54a">
                                <input type="radio" name="recipient" value="{{ $key }}" class="sr-only"
                                    @checked(old('recipient', 'missed') === $key)>
                                <span>{{ $recipient['emoji'] }} {{ __($recipient['name']) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <textarea name="content" rows="7" required minlength="3" maxlength="600" data-count
                    class="letter-textarea" placeholder="{{ __('Write what you never said…') }}"
                    aria-label="{{ __('Your letter') }}">{{ old('content') }}</textarea>
                <p class="char-count text-end text-xs opacity-60">0 / 600</p>

                {{-- What happens to it? --}}
                <p class="mt-4 mb-2 font-bold">{{ __('And now?') }}</p>
                <div class="grid gap-2 sm:grid-cols-3">
                    <button type="submit" name="action" value="keep" class="letter-choice">
                        <span class="text-2xl">✉️</span>
                        <span class="font-bold">{{ __('Keep it for me') }}</span>
                        <span class="text-xs opacity-70">{{ __('Only you can see it') }}</span>
                    </button>
                    <button type="submit" name="action" value="release" class="letter-choice">
                        <span class="text-2xl">🌙</span>
                        <span class="font-bold">{{ __('Let it go') }}</span>
                        <span class="text-xs opacity-70">{{ __('It disappears, nothing is saved') }}</span>
                    </button>
                    <button type="submit" name="action" value="publish" class="letter-choice">
                        <span class="text-2xl">🤍</span>
                        <span class="font-bold">{{ __('Share as anonymous bawh') }}</span>
                        <span class="text-xs opacity-70">{{ __('Everyone can read it, without your name') }}</span>
                    </button>
                </div>
            </form>

            {{-- ✉️ My letters (private) --}}
            <section class="space-y-4">
                <h2 class="section-title">✉️ {{ __('My letters') }}</h2>
                <div class="letters-grid">
                    @forelse ($myLetters as $letter)
                        <x-letter-card :letter="$letter" :mine="true" />
                    @empty
                        <p class="empty-box col-span-full">{{ __('You haven\'t kept any letters yet.') }}</p>
                    @endforelse
                </div>
            </section>
        @else
            <a href="{{ route('login') }}" class="composer reveal">
                <span class="flex-1 ps-2 text-start opacity-80">{{ __('Log in to write your letter ✉️') }}</span>
                <span class="composer-btn">{{ __('Log In') }}</span>
            </a>
        @endauth

        {{-- 🤍 Letters from everyone (anonymous) --}}
        <section class="space-y-4">
            <h2 class="section-title">🤍 {{ __('Letters that were never sent') }}</h2>
            <div class="letters-grid">
                @forelse ($publicLetters as $letter)
                    <x-letter-card :letter="$letter" :mine="auth()->id() === $letter->user_id" />
                @empty
                    <p class="empty-box col-span-full">{{ __('No letters shared yet. Yours could be the first.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

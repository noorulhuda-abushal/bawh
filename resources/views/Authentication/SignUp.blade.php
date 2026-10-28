@extends('Layout.AuthImg')
@extends('Layout.app')

@section('title', __('Sign Up'))
@section('content')
@section('form')

    <div>
        <form method="POST" action="{{ route('signup') }}" class="auth-card reveal">
            @csrf
            <h2 class="mb-4 text-center text-3xl font-bold">{{ __('Sign Up') }}</h2>

            <div class="mb-4">
                <label for="name" class="label">{{ __('Name') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                    class="input peer user-invalid:border-red-500 @error('name') border-red-500 @enderror"
                    required minlength="2" maxlength="50" pattern="[A-Za-z؀-ۿ ]+">
                <p class="mt-1 text-xs opacity-75 peer-user-invalid:text-red-600">
                    {{ __('2–50 characters, letters only.') }}
                </p>
                @error('name')
                    <p class="mt-1 text-sm font-semibold text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="email" class="label">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    class="input peer user-invalid:border-red-500 @error('email') border-red-500 @enderror" required
                    pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}">
                <p class="mt-1 text-xs opacity-75 peer-user-invalid:text-red-600">
                    {{ __('A valid email, e.g. name@example.com') }}
                </p>
                {{-- Error from the server (e.g. email already registered) --}}
                @error('email')
                    <p class="mt-1 text-sm font-semibold text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="label">{{ __('Password') }}</label>
                <input id="password" name="password" type="password"
                    class="input peer user-invalid:border-red-500 @error('password') border-red-500 @enderror" required
                    minlength="8">
                <p class="mt-1 text-xs opacity-75 peer-user-invalid:text-red-600">
                    {{ __('At least 8 characters.') }}
                </p>
                @error('password')
                    <p class="mt-1 text-sm font-semibold text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <p class="mb-4 text-sm">
                {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="text-tuwaiq-orange hover:underline">{{ __('Log In') }}</a>
            </p>

            <button type="submit" class="btn-primary w-full">{{ __('Sign Up') }}</button>
        </form>
    </div>

@endsection
@endsection

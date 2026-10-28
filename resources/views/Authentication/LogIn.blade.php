@extends('Layout.AuthImg')
@extends('Layout.app')

@section('title', __('Log In'))
@section('content')
@section('form')

    <div>
        <form method="POST" action="{{ route('login') }}" class="auth-card reveal">
            @csrf

            <h2 class="mb-2 text-center text-3xl font-bold">{{ __('Log In') }}</h2>

            <div class="mb-4">
                <label for="email" class="label">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    class="input peer user-invalid:border-red-500 @error('email') border-red-500 @enderror" required
                    pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}">
                <p class="mt-1 text-xs opacity-75 peer-user-invalid:text-red-600">
                    {{ __('A valid email, e.g. name@example.com') }}
                </p>
                {{-- Error from the server (e.g. wrong email or password) --}}
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
                {{ __("Don't have an account?") }} <a href="{{ route('signup') }}" class="text-tuwaiq-orange hover:underline">{{ __('Sign Up') }}</a>
            </p>

            <button type="submit" class="btn-primary w-full">{{ __('Log In') }}</button>
        </form>
    </div>

@endsection
@endsection

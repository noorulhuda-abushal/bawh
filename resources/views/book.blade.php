@extends('Layout.app')

@section('title', $book->title)
@section('content')
    <div class="mx-auto max-w-5xl space-y-10 px-6 pt-8 pb-24">

        <a href="{{ route('library') }}" class="reveal inline-block opacity-70 hover:opacity-100">{{ __('← Back to the library') }}</a>

        {{-- 📖 The book --}}
        <section class="book-hero reveal">
            <div class="book-cover big" style="--cover: {{ $book->coverColor() }}">
                <span class="book-title" dir="auto">{{ $book->title }}</span>
                @if ($book->author)
                    <span class="book-author" dir="auto">{{ $book->author }}</span>
                @endif
            </div>

            <div class="flex-1">
                <h1 class="section-title" dir="auto">{{ $book->title }}</h1>
                @if ($book->author)
                    <p class="mt-1 text-lg opacity-80" dir="auto">✍️ {{ $book->author }}</p>
                @endif
                <p class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    <span class="streak-pill">{{ __(':count quotes from this book', ['count' => $quotes->count()]) }}</span>
                    @if ($book->verified)
                        <span class="ai-tag">✨ {{ __('Confirmed by AI') }}</span>
                    @else
                        <span class="mine-tag">{{ __('Not checked yet') }}</span>
                    @endif
                </p>

                {{-- 🛡️ Admin can remove a wrong book --}}
                @if (auth()->user()?->is_admin)
                    <form method="POST" action="{{ route('delete_book', $book) }}" class="mt-4"
                        data-confirm="{{ __('Remove this book from the library? Its quotes will stay.') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-btn danger">🛡️ {{ __('Remove book') }}</button>
                    </form>
                @endif
            </div>
        </section>

        {{-- The quotes from this book --}}
        <section class="space-y-4">
            <h2 class="section-title">{{ __('Words from this book') }}</h2>
            <div class="quote-grid">
                @forelse ($quotes as $quote)
                    <x-quote-card :quote="$quote" :favorite-ids="$favoriteIds" :reacted="$reacted" />
                @empty
                    <p class="empty-box col-span-full">{{ __('No quotes from this book yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

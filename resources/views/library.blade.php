@extends('Layout.app')

@section('title', __('Library'))
@section('content')
    <div class="mx-auto max-w-5xl space-y-12 px-6 pt-10 pb-24">

        {{-- 📚 Header --}}
        <header class="text-center">
            <p class="kicker reveal">📚 {{ __('Bawh library') }}</p>
            <h1 class="section-title reveal">{{ __('Every quote has a book behind it') }}</h1>
            <p class="hero-sub reveal mx-auto mt-2">{{ __('The books our writers quoted. Open a book to read all the words taken from it.') }}</p>
        </header>

        {{-- ✨ Suggest me a book --}}
        <section id="suggest" class="mirror reveal">
            <p class="kicker">✨ {{ __('Suggest me a book') }}</p>
            <h2 class="section-title">{{ __('Tell me how you feel, and I\'ll find you a book') }}</h2>

            <form id="suggest-form" data-url="{{ route('library_suggest') }}" class="mirror-form">
                <input id="suggest-input" type="text" maxlength="200" required autocomplete="off" class="input"
                    aria-label="{{ __('Your feeling') }}" placeholder="{{ __('e.g. I need hope, I miss someone, I feel lost…') }}">
                <button type="submit" class="btn-glow shrink-0">📖 {{ __('Suggest') }}</button>
            </form>

            <div id="suggest-result" class="suggest-result" hidden aria-live="polite">
                <a id="suggest-book" class="book-cover small" href="#">
                    <span class="book-title" id="suggest-title"></span>
                    <span class="book-author" id="suggest-author"></span>
                </a>
                <div class="flex-1">
                    <span id="suggest-by" class="ai-tag" hidden>✨ {{ __('Read by AI') }}</span>
                    <p id="suggest-reason" class="mt-2 font-bold"></p>
                    <p id="suggest-quote" class="quote-text mt-2 opacity-90" dir="auto"></p>
                    <a id="suggest-link" class="btn-ghost mt-3" href="#">📖 {{ __('Open the book') }}</a>
                </div>
            </div>
            <div id="suggest-support" hidden class="mt-4"><x-support-box /></div>
        </section>

        {{-- 🔍 Search + the shelf --}}
        <section class="space-y-5">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="section-title">📚 {{ __('The shelf') }} <span class="text-lg opacity-70">({{ $books->count() }})</span></h2>
                <form method="GET" action="{{ route('library') }}" class="search" role="search">
                    <span aria-hidden="true">🔍</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search a book or author…') }}" aria-label="{{ __('Search') }}">
                </form>
            </div>

            {{-- ✨ Found by the AI: the book is real but nobody quoted it yet --}}
            @if ($found)
                <div class="found-book reveal">
                    <a href="{{ route('book', $found) }}" class="book-cover small" style="--cover: {{ $found->coverColor() }}">
                        <span class="book-title" dir="auto">{{ $found->title }}</span>
                        @if ($found->author)<span class="book-author" dir="auto">{{ $found->author }}</span>@endif
                    </a>
                    <div class="flex-1">
                        <span class="ai-tag">✨ {{ __('Found by AI') }}</span>
                        <p class="mt-2 text-lg font-bold">{{ $found->title }}@if ($found->author) — {{ $found->author }}@endif</p>
                        <p class="mt-1 text-sm opacity-80">{{ __('It\'s not on our shelf yet because nobody quoted it. Be the first!') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('book', $found) }}" class="btn-ghost">📖 {{ __('Open the book') }}</a>
                            @auth
                                <a href="{{ route('home', ['book' => $found->id]) }}" class="btn-glow">✍️ {{ __('Quote from this book') }}</a>
                            @endauth
                        </div>
                    </div>
                </div>
            @endif

            <div class="bookshelf">
                @forelse ($books as $book)
                    <a href="{{ route('book', $book) }}" class="book-cover reveal" style="--cover: {{ $book->coverColor() }}; --i: {{ $loop->index }}">
                        <span class="book-title" dir="auto">{{ $book->title }}</span>
                        @if ($book->author)
                            <span class="book-author" dir="auto">{{ $book->author }}</span>
                        @endif
                        <span class="book-count">✍️ {{ $book->quotes_count }}@if ($book->read_url) · 📖 {{ __('Free') }}@endif</span>
                        @if ($book->verified)
                            <span class="book-verified" title="{{ __('The AI confirmed this book') }}">✓</span>
                        @endif
                    </a>
                @empty
                    @unless ($found)
                        <p class="empty-box col-span-full">
                            {{ $search !== '' ? __('We couldn\'t find this book. Check its name.') : __('The library is still empty. Add a book to your next quote!') }}
                        </p>
                    @endunless
                @endforelse
            </div>
        </section>
    </div>
@endsection

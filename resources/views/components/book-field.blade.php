@props(['book' => null])

{{-- 📚 Optional: which book is the quote from? The AI checks the book and finds its author --}}
<details class="book-field" @if (old('book_title', $book?->title)) open @endif>
    <summary>📖 {{ __('From a book? (optional)') }}</summary>

    <div class="mt-3 grid gap-2 sm:grid-cols-2">
        <input type="text" name="book_title" maxlength="150" class="input" data-book-title
            value="{{ old('book_title', $book?->title) }}"
            placeholder="{{ __('Book title, e.g. Little Women') }}" aria-label="{{ __('Book title') }}">
        <input type="text" name="book_author" maxlength="100" class="input" data-book-author
            value="{{ old('book_author', $book?->author) }}"
            placeholder="{{ __('Author (optional, the AI can find it)') }}" aria-label="{{ __('Author') }}">
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-3">
        <button type="button" class="book-check-btn" data-url="{{ route('check_book') }}">✨ {{ __('Check the book') }}</button>
        <span class="book-check-result text-sm" aria-live="polite"></span>
    </div>

    @error('book_title')
        <p class="mt-2 text-sm font-semibold text-red-400">{{ $message }}</p>
    @enderror
</details>

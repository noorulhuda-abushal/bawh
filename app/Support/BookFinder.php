<?php

namespace App\Support;

use App\Models\Book;
use Illuminate\Support\Facades\Cache;

/**
 * 📚 Finds the book a user wrote, and asks the AI if it really exists and who wrote it.
 *
 * check() returns one of:
 *   ['status' => 'found',      'title' => …, 'author' => …]  the AI confirmed the book
 *   ['status' => 'not_found']                                  the AI couldn't find a real book with this name
 *   ['status' => 'unchecked',  'title' => …, 'author' => …]  no AI (no key / no internet): saved as written
 */
class BookFinder
{
    public static function check(string $title, ?string $author = null): array
    {
        $title = trim($title);
        $author = filled($author) ? trim($author) : null;

        // Already in our library and confirmed? No need to ask the AI again
        $known = self::known($title, $author);
        if ($known) {
            return ['status' => 'found', 'title' => $known->title, 'author' => $known->author];
        }

        if (! Ai::enabled()) {
            return ['status' => 'unchecked', 'title' => $title, 'author' => $author];
        }

        // The same question is remembered for a day, so the AI isn't asked twice (when checking, then sending)
        $key = 'book-check:' . md5(Book::makeSlug($title, $author));
        if ($saved = Cache::get($key)) {
            return $saved;
        }

        $result = self::askAi($title, $author);

        // Remember only clear answers (not "unchecked", so the AI is asked again next time)
        if ($result['status'] !== 'unchecked') {
            Cache::put($key, $result, now()->addDay());
        }

        return $result;
    }

    /**
     * A confirmed book we already have: same title and author,
     * or (if no author was written) the same title by any author.
     */
    private static function known(string $title, ?string $author): ?Book
    {
        $exact = Book::where('slug', Book::makeSlug($title, $author))->where('verified', true)->first();
        if ($exact || $author) {
            return $exact;
        }

        return Book::where('slug', 'like', FeelingMirror::clean($title) . '|%')->where('verified', true)->first();
    }

    private static function askAi(string $title, ?string $author): array
    {
        $answer = Ai::ask(
            'You are a librarian. The user gives a book title (and maybe an author). '
            . 'Decide if a real, published book with this title exists. Arabic and English books are both possible. '
            . 'If it exists, give its correct full title and its author, written in the same language the user used. '
            . 'If you are not sure the book is real, say it does not exist. '
            . 'Reply with JSON only, like: {"exists": true, "title": "...", "author": "..."}',
            'Title: ' . $title . ($author ? "\nAuthor: " . $author : ''),
            150
        );

        // No answer from the AI → keep what the user wrote
        if ($answer === null) {
            return ['status' => 'unchecked', 'title' => $title, 'author' => $author];
        }

        $json = json_decode((string) preg_replace('/^[^{]*|[^}]*$/s', '', $answer), true);

        if (! is_array($json) || ! array_key_exists('exists', $json)) {
            return ['status' => 'unchecked', 'title' => $title, 'author' => $author];
        }

        if (! $json['exists']) {
            return ['status' => 'not_found'];
        }

        return [
            'status' => 'found',
            'title' => mb_substr(trim((string) ($json['title'] ?? $title)) ?: $title, 0, 150),
            'author' => mb_substr(trim((string) ($json['author'] ?? $author)) ?: (string) $author, 0, 100) ?: null,
        ];
    }

    /**
     * Saves the book (or finds it if it's already saved) and returns it.
     */
    public static function save(array $result): Book
    {
        $book = Book::firstOrCreate(
            ['slug' => Book::makeSlug($result['title'], $result['author'])],
            ['title' => $result['title'], 'author' => $result['author']]
        );

        // A book can become verified later, but never goes back to unverified
        if ($result['status'] === 'found' && ! $book->verified) {
            $book->verified = true;
            $book->save();
        }

        return $book;
    }
}

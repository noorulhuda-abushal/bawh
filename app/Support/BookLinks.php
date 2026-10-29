<?php

namespace App\Support;

use App\Models\Book;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 📖 Looks for a FREE and LEGAL copy of a book in 3 open libraries:
 *   1) Project Gutenberg (through the Gutendex API)  – old books whose copyright ended
 *   2) Wikisource (Arabic or English)                 – free texts written by volunteers
 *   3) Open Library (only books marked "public")      – free scans from the Internet Archive
 * If none of them has the book, the book page says there is no free copy.
 */
class BookLinks
{
    // Look again after 30 days (a library may add the book later)
    public const RECHECK_DAYS = 30;

    /**
     * Checks the book if it was never checked (or the last check is old) and saves the result.
     */
    public static function refreshIfNeeded(Book $book): void
    {
        if ($book->read_url || ($book->read_checked_at && $book->read_checked_at->gt(now()->subDays(self::RECHECK_DAYS)))) {
            return;
        }

        $failed = 0;
        foreach (['gutenberg', 'wikisource', 'openLibrary'] as $source) {
            try {
                if ($found = self::$source($book)) {
                    [$url, $name] = $found;
                    $book->forceFill(['read_url' => $url, 'read_source' => $name, 'read_checked_at' => now()])->save();

                    return;
                }
            } catch (Throwable $e) {
                $failed++; // no internet or the library didn't answer
            }
        }

        // Save "checked, nothing found" only if the libraries really answered
        if ($failed < 3) {
            $book->forceFill(['read_checked_at' => now()])->save();
        }
    }

    // 1) 📚 Project Gutenberg
    private static function gutenberg(Book $book): ?array
    {
        $response = self::get('https://gutendex.com/books/', ['search' => trim($book->title . ' ' . $book->author)]);

        foreach ($response->json('results', []) as $result) {
            $names = collect($result['authors'] ?? [])->pluck('name')->implode(' ');
            if (self::sameTitle($book->title, $result['title'] ?? '') && self::sameAuthor($book->author, $names)) {
                return ['https://www.gutenberg.org/ebooks/' . $result['id'], 'Project Gutenberg'];
            }
        }

        return null;
    }

    // 2) 📜 Wikisource (Arabic site for Arabic titles, English site for others)
    private static function wikisource(Book $book): ?array
    {
        $lang = preg_match('/\p{Arabic}/u', $book->title) ? 'ar' : 'en';
        $response = self::get("https://{$lang}.wikisource.org/w/api.php", [
            'action' => 'query', 'list' => 'search', 'srsearch' => $book->title, 'srlimit' => 5, 'format' => 'json',
        ]);

        foreach ($response->json('query.search', []) as $page) {
            if (self::sameTitle($book->title, $page['title'] ?? '')) {
                return ["https://{$lang}.wikisource.org/wiki/" . rawurlencode(str_replace(' ', '_', $page['title'])), $lang === 'ar' ? 'ويكي مصدر' : 'Wikisource'];
            }
        }

        return null;
    }

    // 3) 🏛️ Open Library: only books anyone can read for free ("public")
    private static function openLibrary(Book $book): ?array
    {
        $response = self::get('https://openlibrary.org/search.json', array_filter([
            'title' => $book->title,
            'author' => $book->author,
            'fields' => 'key,title,author_name,ebook_access,public_scan_b',
            'limit' => 5,
        ]));

        foreach ($response->json('docs', []) as $doc) {
            $isFree = ($doc['ebook_access'] ?? '') === 'public' || ($doc['public_scan_b'] ?? false) === true;
            if ($isFree && self::sameTitle($book->title, $doc['title'] ?? '') && self::sameAuthor($book->author, implode(' ', $doc['author_name'] ?? []))) {
                return ['https://openlibrary.org' . $doc['key'], 'Open Library'];
            }
        }

        return null;
    }

    private static function get(string $url, array $query)
    {
        return Http::timeout(6)
            ->withHeaders(['User-Agent' => 'Bawh/1.0 (student project)'])
            ->get($url, $query)
            ->throw();
    }

    // "Jane Eyre: An Autobiography" counts as "Jane Eyre"
    private static function sameTitle(string $ours, string $theirs): bool
    {
        $a = FeelingMirror::clean($ours);
        $b = FeelingMirror::clean(preg_split('/[:;(]/u', $theirs)[0]);

        return $a !== '' && ($a === $b || str_starts_with($b, $a . ' ') || str_starts_with($a, $b . ' '));
    }

    // If we know the author, one of their names (3+ letters) must appear in the library's author names
    private static function sameAuthor(?string $ours, string $theirs): bool
    {
        if (blank($ours)) {
            return true;
        }
        $theirs = FeelingMirror::clean($theirs);

        foreach (explode(' ', FeelingMirror::clean($ours)) as $word) {
            if (mb_strlen($word) >= 3 && str_contains($theirs, $word)) {
                return true;
            }
        }

        return false;
    }
}

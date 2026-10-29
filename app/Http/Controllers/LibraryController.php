<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Quote;
use App\Support\Ai;
use App\Support\BookFinder;
use App\Support\FeelingMirror;
use App\Support\Safety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    /**
     * 📚 The library: every book that quotes came from (with search).
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $books = Book::withCount('quotes')
            ->has('quotes')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', '%' . $search . '%')
                ->orWhere('author', 'like', '%' . $search . '%')))
            ->orderByDesc('quotes_count')
            ->orderBy('title')
            ->get();

        $aiEnabled = Ai::enabled();

        return view('library', ['page' => 'Library'] + compact('books', 'search', 'aiEnabled'));
    }

    /**
     * 📖 One book: its title, author and all the quotes taken from it.
     */
    public function show(Book $book)
    {
        $quotes = $book->quotes()->with(['user', 'book'])->withReactionCounts()->latest()->get();

        $favoriteIds = Auth::check() ? Auth::user()->favorites()->pluck('quotes.id')->all() : [];
        $reacted = Auth::check() ? Auth::user()->reactionKeys() : [];

        return view('book', ['page' => 'Library'] + compact('book', 'quotes', 'favoriteIds', 'reacted'));
    }

    /**
     * ✨ "Check the book" button in the share box: does this book exist, and who wrote it?
     */
    public function check(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:150'],
            'author' => ['nullable', 'string', 'max:100'],
        ]);

        $result = BookFinder::check($data['title'], $data['author'] ?? null);

        return response()->json($result + ['message' => match ($result['status']) {
            'found' => __('Found it ✓'),
            'not_found' => __("We couldn't find this book. Check its name, or leave it empty."),
            default => __("The AI isn't available now, the book will be saved as you wrote it."),
        }]);
    }

    /**
     * ✨ "Suggest me a book": the user writes how they feel, and the AI picks a book from the library.
     * Without AI, the feelings dictionary picks the book that has the most quotes with that feeling.
     */
    public function suggest(Request $request)
    {
        $request->validate(['text' => ['required', 'string', 'max:200']]);
        $text = $request->input('text');

        $books = Book::withCount('quotes')->has('quotes')->with('quotes:id,book_id,mood')->get();

        if ($books->isEmpty()) {
            return response()->json(['found' => false, 'message' => __('The library is still empty. Add a book to your next quote!')]);
        }

        [$book, $reason, $by] = $this->pickWithAi($text, $books) ?? $this->pickWithDictionary($text, $books);

        $sample = $book->quotes()->inRandomOrder()->first();

        return response()->json([
            'found' => true,
            'by' => $by,
            'reason' => $reason,
            'support' => Safety::needsSupport($text),
            'book' => [
                'title' => $book->title,
                'author' => $book->author,
                'url' => route('book', $book),
                'color' => $book->coverColor(),
                'count' => $book->quotes_count,
            ],
            'quote' => $sample?->quoted(),
        ]);
    }

    // ✨ Ask the AI to choose one of our books. Returns null if there's no AI or the answer isn't clear.
    private function pickWithAi(string $text, $books): ?array
    {
        if (! Ai::enabled()) {
            return null;
        }

        $list = $books->take(60)->map(function ($book) {
            $moods = $book->quotes->pluck('mood')->countBy()->sortDesc()->keys()->take(2)->implode(', ');

            return "{$book->id}. {$book->title}" . ($book->author ? " — {$book->author}" : '') . " (feelings: {$moods})";
        })->implode("\n");

        $answer = Ai::ask(
            'You are a kind librarian. Here are the books in our library:' . "\n" . $list . "\n\n"
            . 'The reader tells you how they feel. Choose the ONE book from the list that fits them best, '
            . 'and write one short warm sentence (max 140 characters) about why, in the same language the reader used '
            . '(if Arabic, use a soft Saudi dialect). Only choose from the list. '
            . 'Reply with JSON only, like: {"book_id": 3, "reason": "..."}',
            $text,
            150
        );

        $json = json_decode((string) preg_replace('/^[^{]*|[^}]*$/s', '', (string) $answer), true);
        $book = $books->firstWhere('id', (int) ($json['book_id'] ?? 0));

        if (! $book) {
            return null;
        }

        $reason = is_string($json['reason'] ?? null) ? mb_substr(trim($json['reason']), 0, 180) : __('This book has words that match how you feel.');

        return [$book, $reason, 'ai'];
    }

    // 🪞 Without AI: find the feeling with the dictionary, then the book with the most quotes of that feeling
    private function pickWithDictionary(string $text, $books): array
    {
        $mood = FeelingMirror::detect($text);

        if ($mood) {
            $best = $books->sortByDesc(fn ($book) => $book->quotes->where('mood', $mood)->count())->first();
            if ($best->quotes->where('mood', $mood)->count() > 0) {
                $info = Quote::MOODS[$mood];

                return [$best, __('This book has words about :feeling, like what you feel now.', ['feeling' => $info['emoji'] . ' ' . __($info['name'])]), 'dictionary'];
            }
        }

        return [$books->random(), __('Here is a book from our library, maybe its words are waiting for you.'), 'dictionary'];
    }

    /**
     * 🛡️ An admin can remove a book (its quotes stay, without a book).
     */
    public function destroy(Book $book)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $book->delete();

        return redirect()->route('library')->with('toast', __('Book removed from the library'));
    }
}

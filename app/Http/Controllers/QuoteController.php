<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequest;
use App\Models\Quote;
use App\Models\Reaction;
use App\Models\User;
use App\Support\Ai;
use App\Support\FeelingMirror;
use App\Support\Journey;
use App\Support\Questions;
use App\Support\Safety;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    /**
     * Show the quotes for everyone, including guests (FR-01).
     * Can be filtered by feeling (?mood=hope), searched (?q=word) and sorted (?sort=top).
     */
    public function index(Request $request)
    {
        $mood = array_key_exists($request->query('mood'), Quote::MOODS) ? $request->query('mood') : null;
        $search = trim((string) $request->query('q', ''));
        $sort = $request->query('sort') === 'top' ? 'top' : 'new';

        // withReactionCounts() adds felt_count, not_alone_count, touched_count and reactions_count
        $quotes = Quote::with(['user', 'book'])
            ->withReactionCounts()
            ->when($mood, fn ($query) => $query->where('mood', $mood))
            ->when($search !== '', fn ($query) => $query->where('content', 'like', '%' . $search . '%'))
            ->when($sort === 'top', fn ($query) => $query->orderByDesc('reactions_count'))
            ->latest()
            ->get();

        // 🎲 Quote of the Day: a different quote every day (from all quotes)
        $allCount = Quote::count();
        $quoteOfDay = $allCount > 0
            ? Quote::with('user')->orderBy('id')->skip(now()->dayOfYear % $allCount)->first()
            : null;

        // 📊 Numbers about the site
        $stats = [
            'quotes' => $allCount,
            'writers' => User::has('quotes')->count(),
            'reactions' => Reaction::count(),
        ];

        // 🪞 🤍 What the logged-in user saved and reacted to
        $favoriteIds = Auth::check() ? Auth::user()->favorites()->pluck('quotes.id')->all() : [];
        $reacted = Auth::check() ? Auth::user()->reactionKeys() : [];

        $myQuotesCount = Auth::check() ? Auth::user()->quotes()->count() : 0;

        // ✨ Show the AI button only if an AI key is set in .env
        $aiEnabled = Ai::enabled();

        // 🌙 The question of the day and its answers (newest first)
        $question = Questions::text();
        $answers = Answer::whereDate('question_date', now()->toDateString())->latest()->take(9)->get();

        // 📚 "Quote from this book": the share box opens with this book already written
        $prefillBook = $request->filled('book') ? \App\Models\Book::find($request->integer('book')) : null;

        // 📚 How many books are in the library (for the numbers at the top)
        $stats['books'] = \App\Models\Book::has('quotes')->count();

        // 🔥 My streak (days in a row I wrote something)
        $streak = Auth::check() ? Journey::streak(Auth::user()) : 0;

        // 👥 The people I follow (for the "Following" tab)
        $followingIds = Auth::check() ? Auth::user()->following()->pluck('users.id')->all() : [];

        return view('welcome', compact(
            'quotes', 'quoteOfDay', 'stats', 'favoriteIds', 'reacted',
            'myQuotesCount', 'aiEnabled', 'followingIds', 'mood', 'search', 'sort',
            'question', 'answers', 'streak', 'prefillBook'
        ));
    }

    /**
     * 🌌 A random quote for the "Night thoughts" envelope (returns JSON).
     */
    public function random()
    {
        $quote = Quote::with('user')->inRandomOrder()->first();

        if (! $quote) {
            return response()->json(['message' => __('No quotes yet. Be the first to share one!')], 404);
        }

        return response()->json($this->toJson($quote));
    }

    /**
     * 🪞 Feeling mirror: the user writes how they feel, we find the feeling and send matching quotes.
     * 1) With AI (if there's a key): the AI understands any sentence and writes a kind message.
     * 2) Without AI, or if the AI doesn't answer: the dictionary (app/Support/FeelingMirror.php).
     */
    public function mirror(Request $request)
    {
        $request->validate(['text' => ['required', 'string', 'max:200']]);
        $text = $request->input('text');

        // 💙 If the words talk about hurting oneself, the page shows where to find help
        $support = Safety::needsSupport($text);

        [$feeling, $aiMessage] = $this->feelingFromAi($text);
        $by = $feeling ? 'ai' : 'dictionary';

        $feeling ??= FeelingMirror::detect($text);

        if (! $feeling) {
            return response()->json([
                'found' => false,
                'message' => __("I couldn't read this feeling yet. Try another word, like: tired, I miss, alone…"),
                'support' => $support,
            ]);
        }

        $info = Quote::MOODS[$feeling];
        $quotes = Quote::with('user')->where('mood', $feeling)->inRandomOrder()->take(3)->get();

        return response()->json([
            'found' => true,
            'mood' => $feeling,
            'label' => $info['emoji'] . ' ' . __($info['name']),
            'color' => $info['color'],
            'message' => $aiMessage ?: __(FeelingMirror::MESSAGES[$feeling]),
            'by' => $by,
            'support' => $support,
            'empty' => __('No bawh with this feeling yet. Be the first to write one.'),
            'quotes' => $quotes->map(fn ($quote) => $this->toJson($quote))->all(),
        ]);
    }

    /**
     * Share a new quote (FR-05).
     */
    public function store(QuoteRequest $request)
    {
        $isFirst = Auth::user()->quotes()->doesntExist();

        $quote = Auth::user()->quotes()->create($request->quoteData());

        // ✨ "new_quote" makes the new card land with an animation, and the page scrolls to it
        return redirect(route('home') . '#quote-' . $quote->id)
            ->with('new_quote', $quote->id)
            ->with('support', Safety::needsSupport($quote->content))
            ->with('toast', __('Quote shared ✓'))
            ->with('celebrate', $isFirst); // 🎉 confetti for the first quote
    }

    /**
     * Edit a quote, only if it belongs to the logged-in user (FR-06).
     */
    public function update(QuoteRequest $request, Quote $quote)
    {
        abort_if($quote->user_id !== Auth::id(), 403);

        $quote->update($request->quoteData());

        return redirect()->route('home')->with('toast', __('Quote updated ✓'));
    }

    /**
     * Delete a quote: its owner can (FR-07), and 🛡️ an admin can delete any quote.
     */
    public function destroy(Quote $quote)
    {
        abort_if($quote->user_id !== Auth::id() && ! Auth::user()->is_admin, 403);

        $quote->delete();

        return redirect()->back()->with('toast', __('Quote deleted'));
    }

    /**
     * ✨ Ask the AI which of the 7 feelings the text has, and for one kind sentence.
     * Returns [feeling, message], or [null, null] if there's no AI or its answer isn't clear.
     */
    private function feelingFromAi(string $text): array
    {
        if (! Ai::enabled()) {
            return [null, null];
        }

        $moods = implode(', ', array_keys(Quote::MOODS));
        $answer = Ai::ask(
            "You read how a person feels. Choose the ONE closest feeling from this list: {$moods}. "
            .'Then write one short, warm sentence (max 120 characters) to them, in the same language they wrote in '
            .'(if Arabic, use a soft Saudi dialect). Never give advice or diagnose. '
            .'If they mention wanting to hurt themselves, gently encourage them to talk to someone they trust or a professional. '
            .'Reply with JSON only, like: {"mood":"sad","message":"..."}',
            $text,
            150
        );

        // Take the JSON part of the answer and check it
        $json = json_decode((string) preg_replace('/^[^{]*|[^}]*$/s', '', (string) $answer), true);
        $mood = $json['mood'] ?? null;

        if (! is_string($mood) || ! array_key_exists($mood, Quote::MOODS)) {
            return [null, null];
        }

        $message = is_string($json['message'] ?? null) ? mb_substr(trim($json['message']), 0, 160) : null;

        return [$mood, $message];
    }

    // A quote as JSON for JavaScript (anonymous quotes don't send the writer's profile)
    private function toJson(Quote $quote): array
    {
        $mood = $quote->moodInfo();

        return [
            'text' => $quote->quoted(),
            'author' => $quote->authorName(),
            'profile' => $quote->is_anonymous ? null : route('profile', $quote->user),
            'mood' => $mood['emoji'] . ' ' . __($mood['name']),
            'color' => $mood['color'],
            'book' => $quote->book ? '📖 ' . $quote->book->title . ($quote->book->author ? ' — ' . $quote->book->author : '') : null,
        ];
    }
}

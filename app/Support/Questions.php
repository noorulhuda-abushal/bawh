<?php

namespace App\Support;

use App\Models\Answer;
use Illuminate\Support\Facades\Cache;

/**
 * 🌙 The question of the day: a different question every day, for everyone.
 */
class Questions
{
    public const LIST = [
        "What's the thing you wished you had said, but its time passed?",
        'If you got one more chance, who would you give it to?',
        "What changed in you that nobody noticed?",
        'What small thing made you smile this week?',
        'Who do you miss without telling them?',
        'What would you tell yourself five years ago?',
        'What word do you need to hear today?',
        'What are you grateful for that you never said out loud?',
        'Which place feels like home to you?',
        'What are you still waiting for?',
        'What did you learn the hard way?',
        'What makes you feel safe?',
        'What would you do if you were not afraid?',
        'Which memory do you go back to when you feel tired?',
        'What do you want to let go of?',
        'Who taught you something you will never forget?',
        'What is a dream you never told anyone?',
        'What is the kindest thing someone did for you?',
        'If your heart could write one sentence today, what would it be?',
        'What are you proud of, even if nobody saw it?',
        'What do you wish people understood about you?',
    ];

    /**
     * 🌙 Today's question: ['ar' => …, 'en' => …], the same one for everyone today.
     *
     * The first visit of a new day:
     *   1) the AI writes a new question (in Arabic and English), different from the last days
     *   2) the answers of the days before are deleted (only today's answers stay)
     * Without AI, a question from the list above is used.
     * The question is saved for the whole day, so it never changes while people are answering it.
     */
    public static function today(): array
    {
        $date = now()->toDateString();

        return Cache::remember('daily-question:' . $date, now()->endOfDay(), function () use ($date) {
            // 🧹 A new day: yesterday's answers go away
            Answer::whereDate('question_date', '<', $date)->delete();

            return self::askAi() ?? self::fromList();
        });
    }

    // The question in the language of the page
    public static function text(): string
    {
        $question = self::today();

        return $question[app()->getLocale()] ?? $question['en'];
    }

    // ✨ Ask the AI for a new question (null if the AI is off or the answer is not good)
    private static function askAi(): ?array
    {
        // the questions of the last 7 days, so the AI doesn't repeat them
        $recent = collect(range(1, 7))
            ->map(fn ($days) => Cache::get('daily-question:' . now()->subDays($days)->toDateString())['ar'] ?? null)
            ->filter()
            ->implode(' | ');

        $answer = Ai::ask(
            'You write "the question of the night" for Bawh, an Arabic website where people share their feelings and quotes. '
            . 'Write ONE new question that is short (under 12 words), gentle, deep and personal, about feelings, memories, hopes, people or life. '
            . 'Anyone can answer it in one sentence. No religion, politics or sad questions about death. '
            . 'Write it in simple warm Arabic (Saudi style is fine) and in English. '
            . 'Reply with JSON only: {"ar": "...", "en": "..."}',
            'Today is ' . now()->translatedFormat('l j F') . '.' . ($recent ? ' Do not repeat these recent questions: ' . $recent : ''),
            150
        );

        if ($answer === null) {
            return null;
        }

        $json = json_decode((string) preg_replace('/^[^{]*|[^}]*$/s', '', $answer), true);
        $ar = preg_replace('/^[\s«»"]+|[\s«»"]+$/u', '', (string) ($json['ar'] ?? ''));
        $en = preg_replace('/^[\s«»"]+|[\s«»"]+$/u', '', (string) ($json['en'] ?? ''));

        // a good question: not empty, not too long, and the Arabic one has Arabic letters
        if (mb_strlen($ar) < 6 || mb_strlen($ar) > 120 || mb_strlen($en) < 6 || mb_strlen($en) > 140 || ! preg_match('/\p{Arabic}/u', $ar)) {
            return null;
        }

        return ['ar' => $ar, 'en' => $en, 'ai' => true];
    }

    // 📋 Without AI: a question from the list (a different one every day)
    private static function fromList(): array
    {
        $question = self::LIST[now()->dayOfYear % count(self::LIST)];

        return ['ar' => __($question, [], 'ar'), 'en' => $question, 'ai' => false];
    }
}

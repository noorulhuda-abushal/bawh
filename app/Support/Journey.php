<?php

namespace App\Support;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * 🗓️ A user's journey: their feelings map, their 🔥 streak and their 🏅 badges.
 * Everything is calculated from what they already wrote (no extra tables).
 */
class Journey
{
    /**
     * 🗓️ The last 35 days (5 weeks). For each day: the date and the feeling written most that day (or null).
     */
    public static function moodMap(User $user, bool $includeAnonymous): array
    {
        $from = now()->startOfDay()->subDays(34);

        $quotes = $user->quotes()
            ->where('created_at', '>=', $from)
            ->when(! $includeAnonymous, fn ($q) => $q->where('is_anonymous', false))
            ->get(['mood', 'created_at']);

        // group the feelings by day: ['2026-10-27' => ['sad', 'sad', 'hope'], ...]
        $byDay = $quotes->groupBy(fn ($q) => $q->created_at->toDateString());

        $days = [];
        for ($i = 0; $i < 35; $i++) {
            $date = $from->copy()->addDays($i);
            $moods = $byDay->get($date->toDateString());
            $days[] = [
                'date' => $date,
                // the feeling that appears most that day
                'mood' => $moods ? $moods->countBy('mood')->sortDesc()->keys()->first() : null,
                'count' => $moods ? $moods->count() : 0,
            ];
        }

        return $days;
    }

    /**
     * 💔 How many quotes for each feeling (most first), like ['nostalgia' => 4, 'hope' => 2].
     */
    public static function feelingCounts(User $user, bool $includeAnonymous): array
    {
        return $user->quotes()
            ->when(! $includeAnonymous, fn ($q) => $q->where('is_anonymous', false))
            ->selectRaw('mood, count(*) as total')
            ->groupBy('mood')
            ->orderByDesc('total')
            ->pluck('total', 'mood')
            ->all();
    }

    /**
     * 🔥 Streak: how many days in a row (until today or yesterday) the user wrote something
     * (a quote, a letter or an answer).
     */
    public static function streak(User $user): int
    {
        $dates = collect()
            ->merge($user->quotes()->pluck('created_at'))
            ->merge($user->letters()->pluck('created_at'))
            ->merge($user->answers()->pluck('created_at'))
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->flip();

        $day = now()->startOfDay();
        // if nothing today yet, the streak can still continue from yesterday
        if (! $dates->has($day->toDateString())) {
            $day->subDay();
        }

        $streak = 0;
        while ($dates->has($day->toDateString())) {
            $streak++;
            $day->subDay();
        }

        return $streak;
    }

    /**
     * 🏅 All the badges, each with: emoji, name, how to get it, and if the user has it.
     */
    public static function badges(User $user, int $streak): array
    {
        $quotes = $user->quotes()->count();
        $reactions = \App\Models\Reaction::whereIn('quote_id', $user->quotes()->select('id'))->count();
        // 🌙 a quote written after midnight (12 AM – 4 AM, Saudi time)
        $nightWriter = $user->quotes()->get(['created_at'])->contains(fn ($q) => $q->created_at->hour < 4);

        return [
            ['🤍', 'First bawh', 'Share your first quote', $quotes >= 1],
            ['✍️', 'Writer', 'Share 10 quotes', $quotes >= 10],
            ['🌙', 'Night writer', 'Write after midnight', $nightWriter],
            ['🔥', 'On fire', 'Write 3 days in a row', $streak >= 3],
            ['🫂', 'Reached hearts', 'Get 10 reactions', $reactions >= 10],
            ['💌', 'Letter writer', 'Write a letter that won\'t be sent', $user->letters()->exists()],
            ['🌙', 'Night talker', 'Answer a question of the day', $user->answers()->exists()],
            ['👥', 'Loved', 'Get 5 followers', $user->followers()->count() >= 5],
        ];
    }

    // The feeling name with its emoji, like "🌙 Nostalgia"
    public static function moodLabel(?string $mood): string
    {
        $info = Quote::MOODS[$mood] ?? null;

        return $info ? $info['emoji'] . ' ' . __($info['name']) : '';
    }
}

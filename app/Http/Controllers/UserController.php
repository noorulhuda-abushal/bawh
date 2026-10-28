<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Journey;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * 👤 A user's profile page: their info and their quotes.
     * 🌙 Anonymous quotes show only to their writer (so nobody knows who wrote them).
     * 🪞 "Things like me" shows only on my own profile.
     */
    public function show(User $user)
    {
        $isMe = Auth::id() === $user->id;

        $quotes = $user->quotes()
            ->with('user')
            ->withReactionCounts()
            ->when(! $isMe, fn ($query) => $query->where('is_anonymous', false))
            ->latest()
            ->get();

        // Total reactions this user received on the quotes shown
        $totalReactions = $quotes->sum('reactions_count');

        $likeMe = $isMe
            ? $user->favorites()->with('user')->withReactionCounts()->latest('favorites.created_at')->get()
            : collect();

        // 👥 Followers / following numbers, and do I follow this user?
        $followersCount = $user->followers()->count();
        $followingCount = $user->following()->count();
        $iFollow = Auth::check() && ! $isMe && Auth::user()->following()->where('users.id', $user->id)->exists();

        // 🗓️ 🔥 🏅 Feelings map, streak and badges (anonymous quotes count only on my own profile)
        $moodMap = Journey::moodMap($user, $isMe);
        $feelingCounts = Journey::feelingCounts($user, $isMe);
        $streak = Journey::streak($user);
        $badges = Journey::badges($user, $streak);

        $favoriteIds = Auth::check() ? Auth::user()->favorites()->pluck('quotes.id')->all() : [];
        $reacted = Auth::check() ? Auth::user()->reactionKeys() : [];

        return view('profile', compact(
            'user', 'isMe', 'quotes', 'totalReactions', 'likeMe', 'favoriteIds', 'reacted',
            'followersCount', 'followingCount', 'iFollow',
            'moodMap', 'feelingCounts', 'streak', 'badges'
        ));
    }
}

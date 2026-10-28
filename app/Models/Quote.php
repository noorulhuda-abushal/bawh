<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['content', 'mood', 'is_anonymous'])]
class Quote extends Model
{
    /**
     * 💔 The feelings a quote can have: emoji + color.
     * The names are translated in lang/ar.json.
     */
    public const MOODS = [
        'sad'       => ['emoji' => '💔', 'name' => 'Sadness',    'color' => '#9db8ec'],
        'nostalgia' => ['emoji' => '🌙', 'name' => 'Nostalgia',  'color' => '#cdb8ff'],
        'letdown'   => ['emoji' => '🥀', 'name' => 'Letdown',    'color' => '#f0a6ae'],
        'love'      => ['emoji' => '🤍', 'name' => 'Love',       'color' => '#ffc2d8'],
        'lonely'    => ['emoji' => '🌧️', 'name' => 'Loneliness', 'color' => '#a6d3dc'],
        'hope'      => ['emoji' => '✨', 'name' => 'Hope',       'color' => '#ffe08a'],
        'relief'    => ['emoji' => '🕊️', 'name' => 'Relief',     'color' => '#b5ecd0'],
    ];

    /**
     * 🤍 The 3 reactions instead of a "like".
     */
    public const REACTIONS = [
        'felt'      => ['emoji' => '🤍', 'name' => 'I felt you'],
        'not_alone' => ['emoji' => '🫂', 'name' => "You're not alone"],
        'touched'   => ['emoji' => '✨', 'name' => 'This touched me'],
    ];

    protected function casts(): array
    {
        return ['is_anonymous' => 'boolean'];
    }

    // 🎨 Info about this quote's feeling (falls back to "relief")
    public function moodInfo(): array
    {
        return self::MOODS[$this->mood] ?? self::MOODS['relief'];
    }

    // 🌐 The quote with the right quotation marks: «عربي» or “English”
    public function quoted(): string
    {
        $isArabic = preg_match('/\p{Arabic}/u', $this->content);

        return $isArabic ? '«' . $this->content . '»' : '“' . $this->content . '”';
    }

    // 🌙 The name shown under the quote: the writer's name, or "Anonymous bawh"
    public function authorName(): string
    {
        return $this->is_anonymous ? __('Anonymous bawh') . ' 🌙' : $this->user->name;
    }

    // Each quote belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🪞 The users who said "this is like me" (saved in the favorites table)
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    // 🧹 When it is deleted, its reports are deleted too
    protected static function booted(): void
    {
        static::deleting(fn ($model) => $model->reports()->delete());
    }

    // 🚩 The reports about this quote
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    // 🤍 All the reactions on this quote
    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Adds felt_count, not_alone_count, touched_count and reactions_count to each quote.
     * Use it like: Quote::withReactionCounts()->get()
     */
    public function scopeWithReactionCounts($query)
    {
        $counts = ['reactions'];
        foreach (array_keys(self::REACTIONS) as $type) {
            $counts["reactions as {$type}_count"] = fn ($q) => $q->where('type', $type);
        }

        return $query->withCount($counts);
    }
}

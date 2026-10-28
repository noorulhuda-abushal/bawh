<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * 💌 A letter that won't be sent.
 */
#[Fillable(['recipient', 'content', 'is_public'])]
class Letter extends Model
{
    /**
     * Who the letter is for. The names are translated in lang/ar.json.
     */
    public const RECIPIENTS = [
        'missed'   => ['emoji' => '🌙', 'name' => 'Someone I miss'],
        'letdown'  => ['emoji' => '🥀', 'name' => 'Someone who let me down'],
        'loved'    => ['emoji' => '🤍', 'name' => 'Someone I love'],
        'old_self' => ['emoji' => '🪞', 'name' => 'My old self'],
        'lost'     => ['emoji' => '🕊️', 'name' => 'Someone I lost'],
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public function recipientInfo(): array
    {
        return self::RECIPIENTS[$this->recipient] ?? self::RECIPIENTS['missed'];
    }

    // 🧹 When it is deleted, its reports are deleted too
    protected static function booted(): void
    {
        static::deleting(fn ($model) => $model->reports()->delete());
    }

    // 🚩 The reports about this letter
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    // Each letter belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

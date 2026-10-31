<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * 💬 One note from a visitor about the website.
 */
#[Fillable(['user_id', 'type', 'message', 'email'])]
class Feedback extends Model
{
    // The table is "feedback" (not "feedbacks")
    protected $table = 'feedback';

    // The kinds of notes: [emoji, name]
    public const TYPES = [
        'idea' => ['emoji' => '💡', 'name' => 'An idea'],
        'problem' => ['emoji' => '🐞', 'name' => 'A problem'],
        'praise' => ['emoji' => '🤍', 'name' => 'Kind words'],
    ];

    protected function casts(): array
    {
        return ['is_done' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * 🌙 An answer to the question of the day (always shown without a name).
 */
#[Fillable(['question_date', 'content'])]
class Answer extends Model
{
    protected function casts(): array
    {
        return ['question_date' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🧹 When it is deleted, its reports are deleted too
    protected static function booted(): void
    {
        static::deleting(fn ($model) => $model->reports()->delete());
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}

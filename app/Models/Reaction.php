<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * 🤍 One reaction: a user gave a quote "I felt you", "You're not alone" or "This touched me".
 */
#[Fillable(['user_id', 'quote_id', 'type'])]
class Reaction extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}

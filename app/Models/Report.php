<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * 🚩 One report: a user says a quote / letter / answer is harmful.
 */
#[Fillable(['user_id'])]
class Report extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // The reported thing: a Quote, a Letter or an Answer
    public function reportable()
    {
        return $this->morphTo();
    }
}

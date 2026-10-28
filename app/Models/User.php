<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    // One user has many quotes (1:N)
    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    // 🪞 "Things like me": the quotes this user saved (N:M through the favorites table)
    public function favorites()
    {
        return $this->belongsToMany(Quote::class, 'favorites')->withTimestamps();
    }

    // 🤍 The reactions this user gave (1:N)
    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    // 💌 The letters this user wrote (1:N)
    public function letters()
    {
        return $this->hasMany(Letter::class);
    }

    // 🌙 My answers to the question of the day (1:N)
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    // 👥 The people I follow (N:M users ↔ users through the follows table)
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    // 👥 The people who follow me
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    /**
     * 🤍 Which reactions I gave, as a list like ["5:felt", "5:touched", "8:not_alone"].
     * The quote cards use it to color the buttons I already pressed.
     */
    public function reactionKeys(): array
    {
        return $this->reactions()->get(['quote_id', 'type'])
            ->map(fn ($r) => $r->quote_id . ':' . $r->type)
            ->all();
    }
}

<?php

namespace App\Models;

use App\Support\FeelingMirror;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * 📚 A book that quotes come from.
 */
#[Fillable(['title', 'author', 'slug', 'verified'])]
class Book extends Model
{
    // 🎨 Cover colors: every book gets one (chosen from its id)
    public const COVERS = ['#432a80', '#7a2e4d', '#1f5f5b', '#8a4b14', '#2e4a7a', '#5b2a6e', '#3d5a2a', '#7a3b2e'];

    protected function casts(): array
    {
        return ['verified' => 'boolean', 'read_checked_at' => 'datetime'];
    }

    // The quotes taken from this book
    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function coverColor(): string
    {
        return self::COVERS[$this->id % count(self::COVERS)];
    }

    // "title|author" written in one simple way (no tashkeel, small letters…) so the same book isn't saved twice
    public static function makeSlug(string $title, ?string $author): string
    {
        return FeelingMirror::clean($title) . '|' . FeelingMirror::clean((string) $author);
    }
}

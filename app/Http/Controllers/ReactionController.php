<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Support\Facades\Auth;

class ReactionController extends Controller
{
    /**
     * 🤍 Give a reaction ("felt", "not_alone" or "touched"), or take it back if it's already given.
     * Returns JSON so the page updates without reloading.
     */
    public function toggle(Quote $quote, string $type)
    {
        abort_unless(array_key_exists($type, Quote::REACTIONS), 404);

        $reaction = $quote->reactions()->where('user_id', Auth::id())->where('type', $type)->first();

        if ($reaction) {
            $reaction->delete();
        } else {
            $quote->reactions()->create(['user_id' => Auth::id(), 'type' => $type]);
        }

        return response()->json([
            'active' => ! $reaction,
            'count' => $quote->reactions()->where('type', $type)->count(),
        ]);
    }
}

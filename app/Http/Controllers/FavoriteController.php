<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * 🪞 "This is like me": save the quote, or remove it if it's already saved.
     * (Saved in the favorites table.) Returns JSON so the page updates without reloading.
     */
    public function toggle(Quote $quote)
    {
        $result = Auth::user()->favorites()->toggle($quote->id);

        return response()->json([
            'favorited' => count($result['attached']) > 0,
            'count' => Auth::user()->favorites()->count(),
        ]);
    }
}

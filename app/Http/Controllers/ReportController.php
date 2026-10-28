<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Letter;
use App\Models\Quote;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    // What can be reported: /report/quote/5, /report/letter/2, /report/answer/9
    private const TYPES = [
        'quote' => Quote::class,
        'letter' => Letter::class,
        'answer' => Answer::class,
    ];

    /**
     * 🚩 Report something harmful. The admin sees it in the dashboard.
     * Returns JSON so the page doesn't reload.
     */
    public function store(string $type, int $id)
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $item = self::TYPES[$type]::findOrFail($id);

        // You can't report your own words, and private letters can't be seen so can't be reported
        abort_if($item->user_id === Auth::id(), 422, __("You can't report your own words."));
        abort_if($item instanceof Letter && ! $item->is_public, 404);

        $item->reports()->firstOrCreate(['user_id' => Auth::id()]);

        return response()->json(['message' => __('Thank you, the report reached the admin 🤍')]);
    }
}

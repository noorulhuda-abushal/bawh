<?php

namespace App\Http\Controllers;

use App\Support\Ai;
use Illuminate\Http\Request;

class AiController extends Controller
{
    /**
     * ✨ Send the quote to the AI (Grok or Claude) and get back an improved version.
     * The API key stays here on the server, never in the browser.
     */
    public function improve(Request $request)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'min:3', 'max:155'],
        ]);

        // No key in the .env file → AI is not set up
        if (! Ai::enabled()) {
            return response()->json(['message' => __('AI is not set up yet.')], 503);
        }

        $answer = Ai::ask(
            'You help people polish short quotes. Fix spelling and grammar and make the quote clearer and more touching, '
            .'but keep the same meaning and the same language (if it is Arabic, keep it Arabic). The result must be 155 characters or fewer. '
            .'Reply with the improved quote only, without quotation marks or any explanation.',
            $data['content']
        );

        if (! $answer) {
            return response()->json(['message' => __('AI is busy right now, try again later.')], 502);
        }

        return response()->json([
            'suggestion' => mb_substr(trim($answer, " \n\"“”«»"), 0, 155),
        ]);
    }
}

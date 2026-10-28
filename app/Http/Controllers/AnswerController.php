<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Support\Safety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnswerController extends Controller
{
    /**
     * 🌙 Answer the question of the day (the answer shows without a name).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'answer' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        $answer = Auth::user()->answers()->create([
            'question_date' => now()->toDateString(),
            'content' => $data['answer'],
        ]);

        return redirect(route('home') . '#answer-' . $answer->id)
            ->with('new_answer', $answer->id)
            ->with('support', Safety::needsSupport($data['answer']))
            ->with('toast', __('Your answer joined tonight\'s sky 🌙'));
    }

    /**
     * Delete an answer: its writer can, and 🛡️ an admin can.
     */
    public function destroy(Answer $answer)
    {
        abort_if($answer->user_id !== Auth::id() && ! Auth::user()->is_admin, 403);

        $answer->delete();

        return redirect()->back()->with('toast', __('Answer deleted'));
    }
}

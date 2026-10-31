<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    /**
     * 💬 Anyone (guest or user) can send a note about the website.
     * Errors go to their own "feedback" bag, so they don't mix with the other forms on the page.
     */
    public function store(Request $request)
    {
        $data = $request->validateWithBag('feedback', [
            'type' => ['required', Rule::in(array_keys(Feedback::TYPES))],
            'message' => ['required', 'string', 'min:5', 'max:1000'],
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        Feedback::create($data + ['user_id' => Auth::id()]);

        return redirect()->back()->with('toast', __('Thank you! Your note reached us 🤍'));
    }

    /**
     * 🛡️ Admin: mark a note as done (we improved the site), or open it again.
     */
    public function toggleDone(Feedback $feedback)
    {
        $feedback->is_done = ! $feedback->is_done;
        $feedback->save();

        return redirect()->to(route('admin') . '#feedback')->with('toast', $feedback->is_done ? __('Marked as done ✓') : __('Opened again'));
    }

    /**
     * 🛡️ Admin: delete a note.
     */
    public function destroy(Feedback $feedback)
    {
        $feedback->delete();

        return redirect()->to(route('admin') . '#feedback')->with('toast', __('Note deleted'));
    }
}

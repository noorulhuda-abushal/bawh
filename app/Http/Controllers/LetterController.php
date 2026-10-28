<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Support\Safety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LetterController extends Controller
{
    /**
     * 💌 "A letter that won't be sent" page:
     * the writing box, my kept letters (private), and the published letters (anonymous).
     */
    public function index()
    {
        $myLetters = Auth::check() ? Auth::user()->letters()->latest()->get() : collect();
        $publicLetters = Letter::where('is_public', true)->latest()->take(30)->get();

        return view('letters', ['page' => 'Letters'] + compact('myLetters', 'publicLetters'));
    }

    /**
     * The user chooses what happens to the letter:
     * keep = save it for me only, publish = show it without my name, release = let it go (not saved at all).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient' => ['required', Rule::in(array_keys(Letter::RECIPIENTS))],
            'content' => ['required', 'string', 'min:3', 'max:600'],
            'action' => ['required', Rule::in(['keep', 'publish', 'release'])],
        ]);

        // 💙 Words about hurting oneself → the next page shows where to find help
        $support = Safety::needsSupport($data['content']);

        // 🌙 Let it go: nothing is saved, it just disappears
        if ($data['action'] === 'release') {
            return redirect()->route('letters')
                ->with('support', $support)
                ->with('toast', __('You let it go 🌙 It was not saved anywhere.'));
        }

        $letter = Auth::user()->letters()->create([
            'recipient' => $data['recipient'],
            'content' => $data['content'],
            'is_public' => $data['action'] === 'publish',
        ]);

        // ✨ "new_letter" makes the new letter land with an animation, and the page scrolls to it
        return redirect(route('letters') . '#letter-' . $letter->id)
            ->with('new_letter', $letter->id)
            ->with('support', $support)
            ->with('toast', $data['action'] === 'publish'
                ? __('Published as an anonymous bawh 🤍')
                : __('Kept for you only ✉️'));
    }

    /**
     * Delete a letter: its writer can, and 🛡️ an admin can delete a shared (public) letter.
     * Private letters stay private, even the admin can't touch them.
     */
    public function destroy(Letter $letter)
    {
        $isAdmin = Auth::user()->is_admin && $letter->is_public;
        abort_if($letter->user_id !== Auth::id() && ! $isAdmin, 403);

        $letter->delete();

        return redirect()->back()->with('toast', __('Letter deleted'));
    }
}

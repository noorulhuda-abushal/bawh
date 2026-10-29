<?php

namespace App\Http\Requests;

use App\Models\Quote;
use App\Support\BookFinder;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class QuoteRequest extends FormRequest
{
    /**
     * Only logged-in users can share or edit quotes.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * A quote must be 3 to 155 characters (FR-05), the feeling must be one of the 7 feelings,
     * and "anonymous" is a checkbox (on or off).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:3', 'max:155'],
            'mood' => ['required', Rule::in(array_keys(Quote::MOODS))],
            'is_anonymous' => ['nullable', 'boolean'],
            // 📚 Optional: which book the quote comes from
            'book_title' => ['nullable', 'string', 'min:2', 'max:150'],
            'book_author' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * The data to save. An unchecked checkbox isn't sent at all, so boolean() turns it into false.
     */
    public function quoteData(): array
    {
        return [
            'content' => $this->validated('content'),
            'mood' => $this->validated('mood'),
            'is_anonymous' => $this->boolean('is_anonymous'),
            'book_id' => $this->bookId(),
        ];
    }

    /**
     * 📚 The book of the quote: empty if no book was written.
     * The AI checks the book; if it says the book doesn't exist, the form shows an error.
     */
    private function bookId(): ?int
    {
        if (blank($this->input('book_title'))) {
            return null;
        }

        $result = BookFinder::check($this->input('book_title'), $this->input('book_author'));

        if ($result['status'] === 'not_found') {
            throw ValidationException::withMessages([
                'book_title' => __("We couldn't find this book. Check its name, or leave it empty."),
            ]);
        }

        return BookFinder::save($result)->id;
    }
}

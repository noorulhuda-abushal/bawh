<?php

namespace App\Http\Requests;

use App\Models\Quote;
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
        ];
    }
}

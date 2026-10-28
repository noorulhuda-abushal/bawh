<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SignupRequest extends FormRequest
{
    /**
     * Only guests can sign up.
     */
    public function authorize(): bool
    {
        return Auth::guest();
    }

    /**
     * Name 2–50 letters, valid unique email, password 8+ characters (FR-02).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL\s]+$/u'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
        ];
    }

    /**
     * Friendly error messages shown under each field.
     */
    public function messages(): array
    {
        return [
            'name.regex' => __('The name may only contain letters.'),
            'email.unique' => __('This email is already registered.'),
        ];
    }
}

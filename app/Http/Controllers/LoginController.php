<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        if (Auth::attempt($request->validated())) {
            $request->session()->regenerate();

            return redirect()->route('home')->with('toast', __('Welcome back, :name 👋', ['name' => Auth::user()->name]));
        }

        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('The email or password is incorrect.')]);
    }
}

<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Home: everyone can see the quotes
Route::get('/', [QuoteController::class, 'index'])->name('home');

// 🌐 Switch the language: /lang/ar or /lang/en
Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['ar', 'en']), 404);
    session(['locale' => $locale]);

    return redirect()->back();
})->name('lang');

// 🌌 A random quote for the "Night thoughts" envelope
Route::get('/quotes/random', [QuoteController::class, 'random'])->name('random_quote');

// 🪞 Feeling mirror: write how you feel, get matching quotes
Route::get('/mirror', [QuoteController::class, 'mirror'])->name('mirror');

// 💌 Letters that won't be sent (everyone can read the published ones)
Route::get('/letters', [LetterController::class, 'index'])->name('letters');

// 🚪 Opening /logout in the address bar (or refreshing it) just goes home.
// Logging out itself stays a POST (the button), so no link on another site can log you out.
Route::get('/logout', fn () => redirect()->route('home'));

// 📚 The library: all the books quotes came from, and one book's page
Route::get('/library', [LibraryController::class, 'index'])->name('library');
Route::get('/library/{book}', [LibraryController::class, 'show'])->name('book');
// ✨ The AI suggests a book from the library for your feeling
Route::post('/library/suggest', [LibraryController::class, 'suggest'])->name('library_suggest');

// 👤 Profile page of any user: /users/5
Route::get('/users/{user}', [UserController::class, 'show'])->name('profile');

// Only guests (not logged in) can open Sign Up and Log In
Route::middleware('guest')->group(function () {
    Route::get('/signup', function () {
        return view('Authentication.SignUp', ['page' => 'Sign Up']);
    })->name('signup');
    Route::post('/signup', [SignupController::class, 'signup']);

    Route::get('/login', function () {
        return view('Authentication.LogIn', ['page' => 'Log In']);
    })->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Only logged-in users can do these
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    Route::post('/', [QuoteController::class, 'store'])->name('store_quote');
    Route::put('/quote/{quote}', [QuoteController::class, 'update'])->name('edit_quote');
    Route::delete('/quote/{quote}', [QuoteController::class, 'destroy'])->name('delete_quote');

    // 🪞 "This is like me" and 🤍 reactions (felt / not_alone / touched)
    Route::post('/quote/{quote}/favorite', [FavoriteController::class, 'toggle'])->name('toggle_favorite');
    Route::post('/quote/{quote}/react/{type}', [ReactionController::class, 'toggle'])->name('toggle_reaction');

    // 👥 Follow / unfollow a user
    Route::post('/users/{user}/follow', [FollowController::class, 'toggle'])->name('toggle_follow');

    // 💌 Write and delete letters
    Route::post('/letters', [LetterController::class, 'store'])->name('store_letter');
    Route::delete('/letters/{letter}', [LetterController::class, 'destroy'])->name('delete_letter');

    // 📚 ✨ Check that a book exists (button in the share box)
    Route::post('/books/check', [LibraryController::class, 'check'])->name('check_book');

    // 🌙 Answer the question of the day
    Route::post('/answers', [AnswerController::class, 'store'])->name('store_answer');
    Route::delete('/answers/{answer}', [AnswerController::class, 'destroy'])->name('delete_answer');

    // 🚩 Report a quote / letter / answer to the admin
    Route::post('/report/{type}/{id}', [ReportController::class, 'store'])->whereNumber('id')->name('report');

    // ✨ AI: improve the wording of a quote
    Route::post('/ai/improve', [AiController::class, 'improve'])->name('ai_improve');
});

// 🛡️ Admin only (logged in + is_admin)
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('admin_delete_user');
    Route::post('/users/{user}/role', [AdminController::class, 'toggleAdmin'])->name('admin_toggle_role');
    Route::delete('/reports/{type}/{id}', [AdminController::class, 'dismissReports'])->whereNumber('id')->name('admin_dismiss_reports');
    Route::delete('/library/{book}', [LibraryController::class, 'destroy'])->name('delete_book');
});

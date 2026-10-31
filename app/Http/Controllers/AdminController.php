<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Letter;
use App\Models\Quote;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * 🛡️ The admin dashboard: numbers about the site, the feelings, the users,
     * the latest quotes and the shared letters (so the admin can remove anything harmful).
     */
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'quotes' => Quote::count(),
            'letters' => Letter::where('is_public', true)->count(),
            'reactions' => Reaction::count(),
            'anonymous' => Quote::where('is_anonymous', true)->count(),
            'feedback' => Feedback::where('is_done', false)->count(),
        ];

        // 💔 How many quotes for each feeling (for the bars)
        $feelings = Quote::selectRaw('mood, count(*) as total')->groupBy('mood')->pluck('total', 'mood');

        $users = User::withCount(['quotes', 'followers'])->latest()->get();
        $quotes = Quote::with('user')->withReactionCounts()->latest()->take(20)->get();
        $letters = Letter::where('is_public', true)->latest()->take(20)->get();

        // 🚩 Reports: grouped by the reported thing, most reported first
        $reports = Report::with('reportable')->get()
            ->filter(fn ($report) => $report->reportable !== null)
            ->groupBy(fn ($report) => $report->reportable_type . ':' . $report->reportable_id)
            ->map(fn ($group) => ['item' => $group->first()->reportable, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        // 💬 Notes from visitors: new ones first, then the done ones
        $feedback = Feedback::with('user')->orderBy('is_done')->latest()->get();

        return view('admin', ['page' => 'Admin'] + compact('stats', 'feelings', 'users', 'quotes', 'letters', 'reports', 'feedback'));
    }

    /**
     * 🚩 The admin looked at a report and the content is fine: remove the reports, keep the content.
     */
    public function dismissReports(string $type, int $id)
    {
        $classes = ['Quote' => \App\Models\Quote::class, 'Letter' => \App\Models\Letter::class, 'Answer' => \App\Models\Answer::class];
        abort_unless(isset($classes[$type]), 404);

        Report::where('reportable_type', $classes[$type])->where('reportable_id', $id)->delete();

        return redirect()->route('admin')->with('toast', __('Reports dismissed'));
    }

    /**
     * Delete a user (and everything they wrote). An admin can't delete themselves.
     */
    public function destroyUser(User $user)
    {
        abort_if($user->id === Auth::id(), 422, __("You can't delete your own account here."));

        $user->delete();

        return redirect()->route('admin')->with('toast', __('User deleted'));
    }

    /**
     * Make a user admin, or take it back.
     */
    public function toggleAdmin(User $user)
    {
        abort_if($user->id === Auth::id(), 422, __("You can't change your own role."));

        $user->is_admin = ! $user->is_admin;
        $user->save();

        return redirect()->route('admin')->with('toast', $user->is_admin
            ? __(':name is now an admin 🛡️', ['name' => $user->name])
            : __(':name is not an admin anymore', ['name' => $user->name]));
    }
}

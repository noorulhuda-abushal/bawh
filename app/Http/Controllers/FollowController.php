<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    /**
     * 👥 Follow a user, or unfollow if I already follow them.
     * Returns JSON so the page updates without reloading.
     */
    public function toggle(User $user)
    {
        // You can't follow yourself
        abort_if($user->id === Auth::id(), 422, __("You can't follow yourself."));

        $result = Auth::user()->following()->toggle($user->id);

        return response()->json([
            'following' => count($result['attached']) > 0,
            'followers' => $user->followers()->count(),
        ]);
    }
}

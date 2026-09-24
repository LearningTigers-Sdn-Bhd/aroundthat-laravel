<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\ChangeUserStatus;
use App\Data\Forms\ReasonData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * An admin suspends or reactivates a login across every business it belongs to.
 */
class UserStatusController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function suspend(Request $request, User $user, ReasonData $data, ChangeUserStatus $status): RedirectResponse
    {
        $status->suspend($request->user(), $user, $data->reason);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Login suspended.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(User $user, ChangeUserStatus $status): RedirectResponse
    {
        $status->reactivate($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Login reactivated.')]);

        return back();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\RecoverUserAccess;
use App\Data\Admin\UserData;
use App\Data\Forms\TemporaryPasswordData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Recovery tab of a login: an admin helps a locked-out user back in.
 */
class UserRecoveryController extends Controller
{
    public function index(User $user): Response
    {
        $user->load('suspendedBy');

        return Inertia::render('admin/users/recovery', [
            'user' => UserData::fromModel($user),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function sendPasswordReset(Request $request, User $user, RecoverUserAccess $recovery): RedirectResponse
    {
        $recovery->sendPasswordReset($request->user(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password reset email sent.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function setTemporaryPassword(Request $request, User $user, TemporaryPasswordData $data, RecoverUserAccess $recovery): RedirectResponse
    {
        $recovery->setTemporaryPassword($request->user(), $user, $data->password);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Temporary password set.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function resetTwoFactor(Request $request, User $user, RecoverUserAccess $recovery): RedirectResponse
    {
        $recovery->resetTwoFactor($request->user(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Two-factor authentication turned off.')]);

        return back();
    }
}

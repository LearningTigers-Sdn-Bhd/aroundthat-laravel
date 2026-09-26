<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Staff\ManageInvitation;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * An admin resends or cancels any business's invitation, such as a first owner's.
 */
class InvitationController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function resend(Invitation $invitation, ManageInvitation $manage): RedirectResponse
    {
        $manage->resend($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent again.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function destroy(Invitation $invitation, ManageInvitation $manage): RedirectResponse
    {
        $manage->cancel($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return back();
    }
}

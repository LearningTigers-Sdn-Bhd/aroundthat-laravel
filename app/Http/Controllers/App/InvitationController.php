<?php

namespace App\Http\Controllers\App;

use App\Actions\Staff\InviteStaff;
use App\Actions\Staff\ManageInvitation;
use App\Data\Forms\InviteStaffData;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * The owner invites staff by email, and resends or cancels invitations that are still open.
 */
class InvitationController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    /**
     * @throws ValidationException
     */
    public function store(Request $request, InviteStaffData $data, InviteStaff $inviteStaff): RedirectResponse
    {
        Gate::authorize('create', [Invitation::class, $this->workspace->business()]);

        $inviteStaff->handle($request->user(), $this->workspace->business(), $data);

        return $this->done(__('Invitation sent to :email.', ['email' => $data->email]));
    }

    /**
     * @throws ValidationException
     */
    public function resend(Invitation $invitation, ManageInvitation $manage): RedirectResponse
    {
        $this->authorizeFor($invitation);

        $manage->resend($invitation);

        return $this->done(__('Invitation sent again.'));
    }

    /**
     * @throws ValidationException
     */
    public function destroy(Invitation $invitation, ManageInvitation $manage): RedirectResponse
    {
        $this->authorizeFor($invitation);

        $manage->cancel($invitation);

        return $this->done(__('Invitation cancelled.'));
    }

    protected function authorizeFor(Invitation $invitation): void
    {
        $this->workspace->ensureOwns($invitation);
        Gate::authorize('update', $invitation);
    }

    protected function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}

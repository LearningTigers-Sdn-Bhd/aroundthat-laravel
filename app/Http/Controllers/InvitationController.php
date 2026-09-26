<?php

namespace App\Http\Controllers;

use App\Actions\Staff\RespondToInvitation;
use App\Data\Forms\NewAccountData;
use App\Data\InvitationPreviewData;
use App\Http\Middleware\ResolveCurrentBusiness;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public page an invitation link opens. Guests and logged-in users both land here.
 */
class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $invitation = $this->findInvitation($token);
        $hasAccount = User::where('email', $invitation->email)->exists();

        if ($hasAccount && $request->user() === null) {
            $request->session()->put('url.intended', $request->url());
        }

        return Inertia::render('invitations/show', [
            'token' => $token,
            'invitation' => InvitationPreviewData::fromInvitation($invitation),
            'hasAccount' => $hasAccount,
            'signedInEmail' => $request->user()?->email,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function accept(Request $request, string $token, RespondToInvitation $respond): RedirectResponse
    {
        $invitation = $this->findInvitation($token);
        $currentUser = $request->user();

        $account = $invitation->isPending() && $currentUser === null && ! User::where('email', $invitation->email)->exists()
            ? NewAccountData::validateAndCreate($request->only('name', 'password', 'password_confirmation'))
            : null;

        $membership = $respond->accept($invitation, $currentUser, $account);

        if ($currentUser === null) {
            Auth::login($membership->user);
            $request->session()->regenerate();
        }

        $request->session()->put(ResolveCurrentBusiness::SESSION_KEY, $membership->business_id);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('You joined :business.', ['business' => $membership->business->name])]);

        return redirect()->route('dashboard');
    }

    /**
     * @throws ValidationException
     */
    public function decline(string $token, RespondToInvitation $respond): RedirectResponse
    {
        $respond->decline($this->findInvitation($token));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation declined.')]);

        return redirect()->route('home');
    }

    protected function findInvitation(string $token): Invitation
    {
        $invitation = Invitation::findByToken($token);

        abort_if($invitation === null, 404);

        return $invitation;
    }
}

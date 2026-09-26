<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

/**
 * An admin helps a user back into a login they are locked out of. Each step is logged, but never the password.
 */
class RecoverUserAccess
{
    public function __construct(
        protected AuditTrail $audit,
        protected DisableTwoFactorAuthentication $disableTwoFactor,
    ) {}

    /**
     * Email the user a link to choose a new password.
     *
     * @throws ValidationException
     */
    public function sendPasswordReset(User $admin, User $user): void
    {
        $this->ensureRecoverable($admin, $user);

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['user' => __($status)]);
        }

        $this->audit->record($user, 'password_reset_sent');
    }

    /**
     * Replace the password with one the admin passes on. The user must change it at their next login.
     *
     * Every session and remember-me cookie holding the old password is signed out on its next request by the
     * AuthenticateSession middleware, whatever the session driver.
     *
     * @throws ValidationException
     */
    public function setTemporaryPassword(User $admin, User $user, string $password): void
    {
        $this->ensureRecoverable($admin, $user);

        DB::transaction(function () use ($user, $password): void {
            $user->forceFill([
                'password' => $password,
                'must_change_password' => true,
                'remember_token' => Str::random(60),
            ])->save();

            $this->audit->record($user, 'temporary_password_set');
        });
    }

    /**
     * Turn off two-factor authentication for a user who lost their authenticator and recovery codes.
     *
     * @throws ValidationException
     */
    public function resetTwoFactor(User $admin, User $user): void
    {
        $this->ensureRecoverable($admin, $user);

        if ($user->two_factor_confirmed_at === null) {
            throw ValidationException::withMessages(['user' => __('Two-factor authentication is already off.')]);
        }

        DB::transaction(function () use ($user): void {
            ($this->disableTwoFactor)($user);

            $this->audit->record($user, 'two_factor_reset');
        });
    }

    /**
     * @throws ValidationException
     */
    protected function ensureRecoverable(User $admin, User $user): void
    {
        $refusal = match (true) {
            $user->is($admin) => __('Change your own login from Settings.'),
            $user->isSuspended() => __('Reactivate this login first.'),
            default => null,
        };

        if ($refusal !== null) {
            throw ValidationException::withMessages(['user' => $refusal]);
        }
    }
}

<?php

namespace App\Actions\Businesses;

use App\Data\Forms\OnboardBusinessData;
use App\Enums\MembershipRole;
use App\Enums\OnboardingStatus;
use App\Enums\OwnerMethod;
use App\Models\Business;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * An admin creates a business and its first owner, and either approves it now or leaves it for the owner to complete.
 */
class OnboardBusiness
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $admin, OnboardBusinessData $data): Business
    {
        return DB::transaction(fn (): Business => $this->audit->as(
            $data->approveImmediately ? 'onboarded_and_approved' : 'onboarded',
            null,
            function () use ($admin, $data): Business {
                $owner = $this->resolveOwner($data);

                $business = new Business($data->business->toModelAttributes());

                if ($data->approveImmediately) {
                    $business->forceFill([
                        'onboarding_status' => OnboardingStatus::Approved,
                        'approved_at' => now(),
                        'approved_by_id' => $admin->getKey(),
                    ]);
                }

                $business->save();
                $business->memberships()->create(['user_id' => $owner->getKey(), 'role' => MembershipRole::Owner]);

                return $business;
            },
        ));
    }

    /**
     * @throws ValidationException
     */
    protected function resolveOwner(OnboardBusinessData $data): User
    {
        $email = Str::lower($data->ownerEmail);

        return match ($data->ownerMethod) {
            OwnerMethod::Existing => $this->existingOwner($email),
            OwnerMethod::TemporaryPassword => $this->temporaryPasswordOwner($email, $data),
        };
    }

    /**
     * @throws ValidationException
     */
    protected function existingOwner(string $email): User
    {
        $user = User::where('email', $email)->first();

        if (! $user || $user->isSuspended() || ! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'owner_email' => __('No active, verified user has that email.'),
            ]);
        }

        return $user;
    }

    /**
     * @throws ValidationException
     */
    protected function temporaryPasswordOwner(string $email, OnboardBusinessData $data): User
    {
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'owner_email' => __('That email already belongs to a login. Choose "existing user" instead.'),
            ]);
        }

        $user = new User(['name' => $data->ownerName, 'email' => $email, 'password' => $data->ownerPassword]);
        $user->forceFill(['email_verified_at' => now(), 'must_change_password' => true])->save();

        return $user;
    }
}

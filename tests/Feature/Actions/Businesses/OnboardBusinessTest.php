<?php

use App\Actions\Businesses\OnboardBusiness;
use App\Data\Forms\OnboardBusinessData;
use App\Enums\MembershipRole;
use App\Enums\OnboardingStatus;
use App\Jobs\SendStaffInvitation;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

function onboardingInput(array $overrides = []): array
{
    return array_replace_recursive([
        'business' => [
            'name' => 'Borneo Restaurant',
            'contact_email' => 'hello@borneo.test',
        ],
        'owner_method' => 'temporary_password',
        'owner_email' => 'Owner@Borneo.test',
        'owner_name' => 'Aminah',
        'owner_password' => 'temporary-password-123',
    ], $overrides);
}

test('creates a draft business with a new owner who must change their temporary password', function () {
    $admin = User::factory()->admin()->create();

    $business = app(OnboardBusiness::class)->handle($admin, OnboardBusinessData::validateAndCreate(onboardingInput()));

    expect($business->refresh()->onboarding_status)->toBe(OnboardingStatus::Draft);
    $owner = User::where('email', 'owner@borneo.test')->sole();
    expect($owner->must_change_password)->toBeTrue();
    expect($owner->hasVerifiedEmail())->toBeTrue();
    expect(Hash::check('temporary-password-123', $owner->password))->toBeTrue();
    expect($owner->membershipFor($business)->role)->toBe(MembershipRole::Owner);
    expect(Activity::forSubject($business)->sole()->event)->toBe('onboarded');
});

test('approves the business at once when the admin chooses to', function () {
    $admin = User::factory()->admin()->create();

    $business = app(OnboardBusiness::class)->handle($admin, OnboardBusinessData::validateAndCreate(onboardingInput([
        'approve_immediately' => true,
    ])));

    expect($business->onboarding_status)->toBe(OnboardingStatus::Approved);
    expect($business->approved_by_id)->toBe($admin->id);
    expect(Activity::forSubject($business)->sole()->event)->toBe('onboarded_and_approved');
});

test('attaches an existing verified user as the owner', function () {
    $admin = User::factory()->admin()->create();
    $existing = User::factory()->create(['email' => 'owner@borneo.test']);

    $business = app(OnboardBusiness::class)->handle($admin, OnboardBusinessData::validateAndCreate(onboardingInput([
        'owner_method' => 'existing',
        'owner_name' => null,
        'owner_password' => null,
    ])));

    expect($existing->membershipFor($business)->role)->toBe(MembershipRole::Owner);
    expect(User::count())->toBe(2);
});

test('rejects an existing owner who is suspended or unverified', function (Closure $makeUser) {
    $admin = User::factory()->admin()->create();
    $makeUser();

    $input = OnboardBusinessData::validateAndCreate(onboardingInput(['owner_method' => 'existing']));

    expect(fn () => app(OnboardBusiness::class)->handle($admin, $input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('businesses', 0);
})->with([
    'suspended' => [fn () => User::factory()->suspended()->create(['email' => 'owner@borneo.test'])],
    'unverified' => [fn () => User::factory()->unverified()->create(['email' => 'owner@borneo.test'])],
    'missing' => [fn () => null],
]);

test('refuses a temporary password owner whose email already has a login', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'owner@borneo.test']);

    $input = OnboardBusinessData::validateAndCreate(onboardingInput());

    expect(fn () => app(OnboardBusiness::class)->handle($admin, $input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('businesses', 0);
});

test('requires a name and password for a temporary password owner', function () {
    OnboardBusinessData::validateAndCreate(onboardingInput(['owner_name' => null, 'owner_password' => null]));
})->throws(ValidationException::class);

test('invites the owner by email and leaves the business without members until they accept', function () {
    Queue::fake([SendStaffInvitation::class]);
    $admin = User::factory()->admin()->create();

    $business = app(OnboardBusiness::class)->handle($admin, OnboardBusinessData::validateAndCreate(onboardingInput([
        'owner_method' => 'invite',
        'owner_name' => null,
        'owner_password' => null,
    ])));

    expect($business->memberships()->count())->toBe(0);
    $invitation = $business->invitations()->sole();
    expect($invitation->email)->toBe('owner@borneo.test');
    expect($invitation->role)->toBe(MembershipRole::Owner);
    expect($invitation->invited_by_id)->toBe($admin->id);
    expect(User::where('email', 'owner@borneo.test')->exists())->toBeFalse();
    Queue::assertPushed(SendStaffInvitation::class);
});

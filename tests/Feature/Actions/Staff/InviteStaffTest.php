<?php

use App\Actions\Staff\InviteStaff;
use App\Data\Forms\InviteStaffData;
use App\Enums\MembershipRole;
use App\Jobs\SendStaffInvitation;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

function inviteToBusiness(Business $business, array $input): Invitation
{
    return app(InviteStaff::class)->handle(User::factory()->create(), $business, InviteStaffData::validateAndCreate($input));
}

test('invites a cashier to outlets and queues the email with a working link', function () {
    Queue::fake([SendStaffInvitation::class]);
    $business = Business::factory()->approved()->create();
    $outlet = Outlet::factory()->for($business)->approved()->create();

    $invitation = inviteToBusiness($business, ['email' => 'Cashier@Borneo.test', 'role' => 'cashier', 'outlet_ids' => [$outlet->id]]);

    expect($invitation->email)->toBe('cashier@borneo.test');
    expect($invitation->role)->toBe(MembershipRole::Cashier);
    expect($invitation->isPending())->toBeTrue();
    expect($invitation->outlets()->pluck('outlets.id')->all())->toBe([$outlet->id]);
    expect(Activity::forSubject($invitation)->sole()->event)->toBe('invited');
    Queue::assertPushed(SendStaffInvitation::class, fn (SendStaffInvitation $job) => $job->invitation->is($invitation)
        && Invitation::findByToken($job->token)?->is($invitation));
});

test('owner invitations take no outlets', function () {
    Queue::fake([SendStaffInvitation::class]);
    $business = Business::factory()->approved()->create();
    $outlet = Outlet::factory()->for($business)->approved()->create();

    $invitation = inviteToBusiness($business, ['email' => 'owner@borneo.test', 'role' => 'owner', 'outlet_ids' => [$outlet->id]]);

    expect($invitation->outlets()->count())->toBe(0);
});

test('refuses a cashier without an operational outlet of the business', function () {
    $business = Business::factory()->approved()->create();
    $draftOutlet = Outlet::factory()->for($business)->create();

    expect(fn () => inviteToBusiness($business, ['email' => 'cashier@borneo.test', 'role' => 'cashier', 'outlet_ids' => [$draftOutlet->id]]))
        ->toThrow(ValidationException::class, 'Select at least one approved, active outlet of this business.');
    $this->assertDatabaseCount('invitations', 0);
});

test('refuses someone who is already a member', function () {
    $membership = Membership::factory()->create();

    expect(fn () => inviteToBusiness($membership->business, ['email' => strtoupper($membership->user->email), 'role' => 'owner']))
        ->toThrow(ValidationException::class, 'This person is already a member of the business.');
});

test('refuses a second invitation while one is pending', function () {
    $pending = Invitation::factory()->create(['email' => 'owner@borneo.test']);

    expect(fn () => inviteToBusiness($pending->business, ['email' => 'owner@borneo.test', 'role' => 'owner']))
        ->toThrow(ValidationException::class, 'This email already has a pending invitation. Resend or cancel it instead.');
});

test('replaces an expired invitation to the same email', function () {
    Queue::fake([SendStaffInvitation::class]);
    $expired = Invitation::factory()->expired()->create(['email' => 'owner@borneo.test']);

    $invitation = inviteToBusiness($expired->business, ['email' => 'owner@borneo.test', 'role' => 'owner']);

    expect($invitation->isPending())->toBeTrue();
    expect($expired->refresh()->cancelled_at)->not->toBeNull();
    expect(Activity::forSubject($expired)->where('event', 'replaced')->exists())->toBeTrue();
});

test('refuses to invite to a suspended business', function () {
    $business = Business::factory()->approved()->suspended()->create();

    expect(fn () => inviteToBusiness($business, ['email' => 'owner@borneo.test', 'role' => 'owner']))
        ->toThrow(ValidationException::class, 'This business is suspended.');
});

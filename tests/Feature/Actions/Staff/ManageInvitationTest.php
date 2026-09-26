<?php

use App\Actions\Staff\ManageInvitation;
use App\Enums\InvitationStatus;
use App\Jobs\SendStaffInvitation;
use App\Models\Activity;
use App\Models\Invitation;
use Database\Factories\InvitationFactory;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

test('resending an expired invitation issues a new link and retires the old one', function () {
    Queue::fake([SendStaffInvitation::class]);
    $invitation = Invitation::factory()->expired()->create(['sent_at' => now()]);
    $oldToken = InvitationFactory::$lastToken;

    app(ManageInvitation::class)->resend($invitation);

    $invitation->refresh();
    expect($invitation->status())->toBe(InvitationStatus::Pending);
    expect($invitation->sent_at)->toBeNull();
    expect(Invitation::findByToken($oldToken))->toBeNull();
    expect(Activity::forSubject($invitation)->where('event', 'resent')->exists())->toBeTrue();
    Queue::assertPushed(SendStaffInvitation::class, fn (SendStaffInvitation $job) => Invitation::findByToken($job->token)?->is($invitation));
});

test('cancelling closes the invitation', function () {
    $invitation = Invitation::factory()->create();

    app(ManageInvitation::class)->cancel($invitation);

    expect($invitation->refresh()->status())->toBe(InvitationStatus::Cancelled);
    expect(Activity::forSubject($invitation)->where('event', 'cancelled')->exists())->toBeTrue();
});

test('answered or cancelled invitations cannot be resent or cancelled', function (string $state, string $method) {
    Queue::fake([SendStaffInvitation::class]);
    $invitation = Invitation::factory()->{$state}()->create();

    expect(fn () => app(ManageInvitation::class)->{$method}($invitation))
        ->toThrow(ValidationException::class, "This invitation was already {$state}.");
    Queue::assertNothingPushed();
})->with(['accepted', 'declined', 'cancelled'])->with(['resend', 'cancel']);

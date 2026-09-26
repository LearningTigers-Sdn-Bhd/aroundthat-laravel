<?php

use App\Jobs\SendStaffInvitation;
use App\Mail\StaffInvitationMail;
use App\Models\Invitation;
use Database\Factories\InvitationFactory;
use Illuminate\Support\Facades\Mail;

test('emails the link and records when it was sent', function () {
    Mail::fake();
    $invitation = Invitation::factory()->create();
    $token = InvitationFactory::$lastToken;

    SendStaffInvitation::dispatchSync($invitation, $token);

    Mail::assertSent(StaffInvitationMail::class, fn (StaffInvitationMail $mail) => $mail->hasTo($invitation->email)
        && $mail->token === $token);
    expect($invitation->refresh()->sent_at)->not->toBeNull();
});

test('the email links to the invitation page', function () {
    $invitation = Invitation::factory()->create();

    $mail = new StaffInvitationMail($invitation, 'the-token');

    $mail->assertSeeInHtml(route('invitations.show', 'the-token'));
    $mail->assertHasSubject("You are invited to {$invitation->business->name}");
});

test('skips links that were replaced or closed after queueing', function (Closure $change) {
    Mail::fake();
    $invitation = Invitation::factory()->create();
    $token = InvitationFactory::$lastToken;
    $change($invitation);

    SendStaffInvitation::dispatchSync($invitation->fresh(), $token);

    Mail::assertNothingSent();
})->with([
    'resent' => [fn (Invitation $invitation) => tap($invitation, fn () => $invitation->issueToken())->save()],
    'cancelled' => [fn (Invitation $invitation) => $invitation->forceFill(['cancelled_at' => now()])->save()],
]);

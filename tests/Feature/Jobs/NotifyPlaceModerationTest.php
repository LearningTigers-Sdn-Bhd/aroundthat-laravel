<?php

use App\Jobs\NotifyPlaceModeration;
use App\Mail\PlaceModerationMail;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('emails the active members who manage public content, and no one else', function () {
    Mail::fake();
    $business = Business::factory()->approved()->create();
    $owner = Membership::factory()->owner()->for($business)->create();
    $manager = Membership::factory()->manager()->for($business)->create();
    $suspendedOwner = Membership::factory()->owner()->suspended()->for($business)->create();
    $blockedOwner = Membership::factory()->owner()->for($business)->for(User::factory()->suspended())->create();
    $outlet = Outlet::factory()->for($business)->approved()->create();

    NotifyPlaceModeration::dispatchSync($outlet, 'hidden', 'Misleading photos.');

    Mail::assertSent(PlaceModerationMail::class, 1);
    Mail::assertSent(PlaceModerationMail::class, fn (PlaceModerationMail $mail) => $mail->hasTo($owner->user->email));
    Mail::assertNotSent(PlaceModerationMail::class, fn (PlaceModerationMail $mail) => $mail->hasTo($manager->user->email)
        || $mail->hasTo($suspendedOwner->user->email)
        || $mail->hasTo($blockedOwner->user->email));
});

test('the revert email names the change and the reason', function () {
    $outlet = Outlet::factory()->approved()->create(['name' => 'Kopi Corner']);

    $mail = new PlaceModerationMail($outlet, 'reverted', 'Wrong tags.', 'tags_changed');

    $mail->assertHasSubject('A change to Kopi Corner was reverted');
    $mail->assertSeeInText('put the tags of Kopi Corner back');
    $mail->assertSeeInText('Wrong tags.');
    $mail->assertSeeInHtml(route('outlets.preview', $outlet));
});

test('the hidden email explains the outlet keeps trading', function () {
    $outlet = Outlet::factory()->approved()->create(['name' => 'Kopi Corner']);

    $mail = new PlaceModerationMail($outlet, 'hidden', 'Misleading photos.');

    $mail->assertHasSubject('Kopi Corner is hidden from visitors');
    $mail->assertSeeInText('It keeps trading');
});

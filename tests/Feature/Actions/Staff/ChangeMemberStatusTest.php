<?php

use App\Actions\Staff\ChangeMemberStatus;
use App\Models\Activity;
use App\Models\Membership;
use Illuminate\Validation\ValidationException;

test('suspends and reactivates a member', function () {
    $owner = Membership::factory()->owner()->create();
    $cashier = Membership::factory()->cashier()->for($owner->business)->create();

    app(ChangeMemberStatus::class)->suspend($owner->user, $cashier, 'Left the company.');

    expect($cashier->refresh()->isActive())->toBeFalse();
    expect($cashier->suspended_by_id)->toBe($owner->user_id);

    app(ChangeMemberStatus::class)->reactivate($cashier);

    expect($cashier->refresh()->isActive())->toBeTrue();
});

test('removes a member and logs the removal', function () {
    $owner = Membership::factory()->owner()->create();
    $cashier = Membership::factory()->cashier()->for($owner->business)->create();

    app(ChangeMemberStatus::class)->remove($cashier);

    $this->assertModelMissing($cashier);
    expect(Activity::forSubject($cashier)->where('event', 'removed')->exists())->toBeTrue();
});

test('refuses to suspend or remove the only owner', function () {
    $owner = Membership::factory()->owner()->create();

    expect(fn () => app(ChangeMemberStatus::class)->suspend($owner->user, $owner, 'No.'))->toThrow(ValidationException::class);
    expect(fn () => app(ChangeMemberStatus::class)->remove($owner))->toThrow(ValidationException::class);
    $this->assertModelExists($owner);
    expect($owner->refresh()->isActive())->toBeTrue();
});

test('refuses to suspend a suspended member or reactivate an active one', function () {
    $owner = Membership::factory()->owner()->create();

    expect(fn () => app(ChangeMemberStatus::class)->suspend($owner->user, Membership::factory()->cashier()->suspended()->for($owner->business)->create(), 'Again'))
        ->toThrow(ValidationException::class);
    expect(fn () => app(ChangeMemberStatus::class)->reactivate(Membership::factory()->cashier()->for($owner->business)->create()))
        ->toThrow(ValidationException::class);
});

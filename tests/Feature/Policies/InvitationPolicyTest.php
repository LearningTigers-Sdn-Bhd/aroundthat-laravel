<?php

use App\Models\Business;
use App\Models\Invitation;
use App\Models\Membership;

test('owners can invite staff and manage their business\'s invitations', function () {
    $owner = Membership::factory()->owner()->create();
    $invitation = Invitation::factory()->for($owner->business)->create();

    expect($owner->user->can('create', [Invitation::class, $owner->business]))->toBeTrue();
    expect($owner->user->can('update', $invitation))->toBeTrue();
});

test('managers and cashiers cannot invite staff', function (string $role) {
    $member = Membership::factory()->{$role}()->create();
    $invitation = Invitation::factory()->for($member->business)->create();

    expect($member->user->can('create', [Invitation::class, $member->business]))->toBeFalse();
    expect($member->user->can('update', $invitation))->toBeFalse();
})->with(['manager', 'cashier']);

test('nobody invites staff to a suspended business', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->suspended())->create();

    expect($owner->user->can('create', [Invitation::class, $owner->business]))->toBeFalse();
});

test('owners cannot manage invitations of another business', function () {
    $owner = Membership::factory()->owner()->create();

    expect($owner->user->can('update', Invitation::factory()->create()))->toBeFalse();
});

<?php

use App\Models\Membership;

test('owners can manage other members of their business', function () {
    $owner = Membership::factory()->owner()->create();
    $cashier = Membership::factory()->cashier()->for($owner->business)->create();

    expect($owner->user->can('viewAny', [Membership::class, $owner->business]))->toBeTrue();
    expect($owner->user->can('update', $cashier))->toBeTrue();
    expect($owner->user->can('delete', $cashier))->toBeTrue();
});

test('members cannot manage their own membership', function () {
    $owner = Membership::factory()->owner()->create();

    expect($owner->user->can('update', $owner))->toBeFalse();
    expect($owner->user->can('delete', $owner))->toBeFalse();
});

test('managers cannot manage staff', function () {
    $manager = Membership::factory()->manager()->create();
    $cashier = Membership::factory()->cashier()->for($manager->business)->create();

    expect($manager->user->can('viewAny', [Membership::class, $manager->business]))->toBeFalse();
    expect($manager->user->can('update', $cashier))->toBeFalse();
});

test('owners cannot manage members of another business', function () {
    $owner = Membership::factory()->owner()->create();
    $otherMember = Membership::factory()->cashier()->create();

    expect($owner->user->can('update', $otherMember))->toBeFalse();
});

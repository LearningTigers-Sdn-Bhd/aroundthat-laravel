<?php

use App\Models\Membership;
use App\Models\User;
use App\Support\EnsureBusinessKeepsAnOwner;
use Illuminate\Validation\ValidationException;

test('rejects a change to the only active owner', function () {
    $owner = Membership::factory()->owner()->create();

    app(EnsureBusinessKeepsAnOwner::class)->forMembership($owner);
})->throws(ValidationException::class, 'A business needs at least one active owner.');

test('allows a change to an owner when another active owner remains', function () {
    $owner = Membership::factory()->owner()->create();
    Membership::factory()->owner()->for($owner->business)->create();

    expect(fn () => app(EnsureBusinessKeepsAnOwner::class)->forMembership($owner))
        ->not->toThrow(ValidationException::class);
});

test('does not count suspended owners or owners whose login is suspended', function () {
    $owner = Membership::factory()->owner()->create();
    Membership::factory()->owner()->suspended()->for($owner->business)->create();
    Membership::factory()->owner()->for($owner->business)->for(User::factory()->suspended())->create();

    app(EnsureBusinessKeepsAnOwner::class)->forMembership($owner);
})->throws(ValidationException::class);

test('allows a change to a non-owner member', function () {
    $owner = Membership::factory()->owner()->create();
    $cashier = Membership::factory()->cashier()->for($owner->business)->create();

    expect(fn () => app(EnsureBusinessKeepsAnOwner::class)->forMembership($cashier))
        ->not->toThrow(ValidationException::class);
});

test('rejects suspending a user who is the only owner of any of their businesses', function () {
    $user = User::factory()->create();
    $shared = Membership::factory()->owner()->for($user)->create();
    Membership::factory()->owner()->for($shared->business)->create();
    Membership::factory()->owner()->for($user)->create();

    app(EnsureBusinessKeepsAnOwner::class)->forUser($user);
})->throws(ValidationException::class);

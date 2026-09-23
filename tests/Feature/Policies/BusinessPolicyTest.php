<?php

use App\Models\Business;
use App\Models\Membership;
use App\Models\User;

test('active members can view their business but not another one', function () {
    $membership = Membership::factory()->cashier()->create();

    expect($membership->user->can('view', $membership->business))->toBeTrue();
    expect($membership->user->can('view', Business::factory()->create()))->toBeFalse();
});

test('suspended members cannot view their business', function () {
    $membership = Membership::factory()->owner()->suspended()->create();

    expect($membership->user->can('view', $membership->business))->toBeFalse();
});

test('only owners can update business details', function (string $role, bool $allowed) {
    $membership = Membership::factory()->{$role}()->create();

    expect($membership->user->can('update', $membership->business))->toBe($allowed);
})->with([
    ['owner', true],
    ['manager', false],
    ['cashier', false],
]);

test('owners can submit a business only while it is draft or rejected', function (string $state, bool $allowed) {
    $business = Business::factory()->{$state}()->create();
    $membership = Membership::factory()->owner()->for($business)->create();

    expect($membership->user->can('submit', $business))->toBe($allowed);
})->with([
    ['rejected', true],
    ['pending', false],
    ['approved', false],
]);

test('system admins get no business rights without a membership', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('update', Business::factory()->create()))->toBeFalse();
});

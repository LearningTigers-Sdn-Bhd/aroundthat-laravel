<?php

use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;

test('cashiers can view only their assigned outlets', function () {
    $assigned = Outlet::factory()->create();
    $unassigned = Outlet::factory()->for($assigned->business)->create();
    $cashier = Membership::factory()->cashier()->for($assigned->business)->withOutlets($assigned)->create();

    expect($cashier->user->can('view', $assigned))->toBeTrue();
    expect($cashier->user->can('view', $unassigned))->toBeFalse();
});

test('only owners can create and update outlets', function (string $role, bool $allowed) {
    $outlet = Outlet::factory()->create();
    $membership = Membership::factory()->{$role}()->for($outlet->business)->withOutlets($outlet)->create();

    expect($membership->user->can('create', [Outlet::class, $outlet->business]))->toBe($allowed);
    expect($membership->user->can('update', $outlet))->toBe($allowed);
})->with([
    ['owner', true],
    ['manager', false],
    ['cashier', false],
]);

test('owners cannot manage outlets of another business', function () {
    $owner = Membership::factory()->owner()->create();
    $otherOutlet = Outlet::factory()->create();

    expect($owner->user->can('update', $otherOutlet))->toBeFalse();
    expect($owner->user->can('create', [Outlet::class, $otherOutlet->business]))->toBeFalse();
});

test('archived outlets cannot be updated', function () {
    $outlet = Outlet::factory()->archived()->create();
    $owner = Membership::factory()->owner()->for($outlet->business)->create();

    expect($owner->user->can('update', $outlet))->toBeFalse();
});

test('owners can submit an outlet only after its business is approved', function () {
    $draftBusinessOutlet = Outlet::factory()->create();
    $approvedBusinessOutlet = Outlet::factory()->for(Business::factory()->approved())->create();
    $owner = Membership::factory()->owner()->for($draftBusinessOutlet->business)->create();
    $otherOwner = Membership::factory()->owner()->for($approvedBusinessOutlet->business)->create();

    expect($owner->user->can('submit', $draftBusinessOutlet))->toBeFalse();
    expect($otherOwner->user->can('submit', $approvedBusinessOutlet))->toBeTrue();
});

test('owners cannot resubmit an outlet that is pending or approved', function (string $state) {
    $outlet = Outlet::factory()->for(Business::factory()->approved())->{$state}()->create();
    $owner = Membership::factory()->owner()->for($outlet->business)->create();

    expect($owner->user->can('submit', $outlet))->toBeFalse();
})->with(['pending', 'approved']);

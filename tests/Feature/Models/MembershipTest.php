<?php

use App\Enums\Ability;
use App\Models\Membership;
use App\Models\Outlet;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('owners reach every outlet of their own business only', function () {
    $owner = Membership::factory()->owner()->create();
    $ownOutlet = Outlet::factory()->for($owner->business)->create();
    $otherOutlet = Outlet::factory()->create();

    expect($owner->canAccessOutlet($ownOutlet))->toBeTrue();
    expect($owner->canAccessOutlet($otherOutlet))->toBeFalse();
});

test('managers and cashiers reach only their assigned outlets', function (string $role) {
    $assigned = Outlet::factory()->create();
    $unassigned = Outlet::factory()->for($assigned->business)->create();
    $membership = Membership::factory()->{$role}()->for($assigned->business)->withOutlets($assigned)->create();

    expect($membership->canAccessOutlet($assigned))->toBeTrue();
    expect($membership->canAccessOutlet($unassigned))->toBeFalse();
    expect($membership->accessibleOutlets()->pluck('outlets.id')->all())->toBe([$assigned->id]);
})->with(['manager', 'cashier']);

test('suspended members can do nothing', function () {
    $outlet = Outlet::factory()->create();
    $membership = Membership::factory()->owner()->suspended()->for($outlet->business)->create();

    expect($membership->canAccessOutlet($outlet))->toBeFalse();
    expect($membership->can(Ability::Scan))->toBeFalse();
});

test('the database rejects an unknown role', function () {
    $membership = Membership::factory()->create();

    DB::table('memberships')->where('id', $membership->id)->update(['role' => 'admin']);
})->throws(QueryException::class, 'memberships_role_check');

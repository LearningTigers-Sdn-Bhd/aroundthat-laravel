<?php

use App\Actions\Staff\UpdateMemberAccess;
use App\Enums\MembershipRole;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use Illuminate\Validation\ValidationException;

test('changes a cashier into a manager at other outlets and logs both changes', function () {
    $first = Outlet::factory()->approved()->create();
    $first->business->forceFill(['onboarding_status' => 'approved'])->save();
    $second = Outlet::factory()->for($first->business)->approved()->create();
    $cashier = Membership::factory()->cashier()->for($first->business)->withOutlets($first)->create();

    app(UpdateMemberAccess::class)->handle($cashier, MembershipRole::Manager, [$second->id]);

    $cashier->refresh();
    expect($cashier->role)->toBe(MembershipRole::Manager);
    expect($cashier->outlets()->pluck('outlets.id')->all())->toBe([$second->id]);
    expect(Activity::forSubject($cashier)->whereIn('event', ['access_changed', 'outlets_changed'])->pluck('event')->all())
        ->toBe(['access_changed', 'outlets_changed']);
});

test('promoting to owner drops assigned outlets', function () {
    $outlet = Outlet::factory()->approved()->create();
    $manager = Membership::factory()->manager()->for($outlet->business)->withOutlets($outlet)->create();

    app(UpdateMemberAccess::class)->handle($manager, MembershipRole::Owner, [$outlet->id]);

    expect($manager->refresh()->role)->toBe(MembershipRole::Owner);
    expect($manager->outlets()->count())->toBe(0);
});

test('refuses outlets that are not operational or belong to another business', function (Closure $makeOutlet) {
    $business = Business::factory()->approved()->create();
    $cashier = Membership::factory()->cashier()->for($business)->create();

    expect(fn () => app(UpdateMemberAccess::class)->handle($cashier, MembershipRole::Cashier, [$makeOutlet($business)->id]))
        ->toThrow(ValidationException::class);
    expect($cashier->outlets()->count())->toBe(0);
})->with([
    'draft outlet' => [fn ($business) => Outlet::factory()->for($business)->create()],
    'archived outlet' => [fn ($business) => Outlet::factory()->for($business)->approved()->archived()->create()],
    'another business' => [fn ($business) => Outlet::factory()->for(Business::factory()->approved())->approved()->create()],
]);

test('refuses a manager or cashier without outlets', function () {
    $cashier = Membership::factory()->cashier()->create();

    app(UpdateMemberAccess::class)->handle($cashier, MembershipRole::Cashier, []);
})->throws(ValidationException::class);

test('refuses to demote the only owner', function () {
    $outlet = Outlet::factory()->approved()->create();
    $owner = Membership::factory()->owner()->for($outlet->business)->create();

    expect(fn () => app(UpdateMemberAccess::class)->handle($owner, MembershipRole::Manager, [$outlet->id]))
        ->toThrow(ValidationException::class);
    expect($owner->refresh()->role)->toBe(MembershipRole::Owner);
});

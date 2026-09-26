<?php

use App\Actions\Staff\UpdateMemberAccess;
use App\Data\Admin\ActivityData;
use App\Data\Admin\BusinessData as AdminBusinessData;
use App\Data\BusinessData;
use App\Data\InvitationData;
use App\Data\MemberData;
use App\Data\OutletData;
use App\Enums\MembershipRole;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;

test('members see their business without the admin-only approval and suspension details', function () {
    $admin = User::factory()->admin()->create();
    $business = Business::factory()->approved()->suspended()->create(['approved_by_id' => $admin->id, 'suspended_by_id' => $admin->id]);

    $data = BusinessData::fromModel($business)->toArray();

    expect(array_keys($data))->toBe([
        'id', 'name', 'registered_name', 'registration_number', 'contact_email', 'contact_phone', 'address',
        'timezone', 'onboarding_status', 'submitted_at', 'rejection_reason', 'is_suspended', 'is_writable',
    ]);
    expect($data['is_suspended'])->toBeTrue();
    expect($data['is_writable'])->toBeFalse();
});

test('members see their outlets without the admin-only approval and suspension details', function () {
    $business = Business::factory()->approved()->create();
    $host = Outlet::factory()->for($business)->approved()->create(['name' => 'Food Court']);
    $outlet = Outlet::factory()->for($business)->approved()->suspended()->create(['host_outlet_id' => $host->id]);

    $data = OutletData::fromModel($outlet)->toArray();

    expect(array_keys($data))->toBe([
        'id', 'name', 'contact_email', 'contact_phone', 'address_line_1', 'address_line_2', 'city', 'state',
        'postcode', 'country_code', 'timezone', 'host_outlet', 'onboarding_status', 'submitted_at',
        'rejection_reason', 'is_suspended', 'archived_at', 'is_operational', 'is_writable', 'is_public', 'hidden_reason',
    ]);
    expect($data['host_outlet'])->toBe(['id' => $host->id, 'name' => 'Food Court']);
    expect($data['is_operational'])->toBeFalse();
});

test('admins see who approved and suspended a business and why', function () {
    $approver = User::factory()->admin()->create(['name' => 'Approver']);
    $suspender = User::factory()->admin()->create(['name' => 'Suspender']);
    $business = Business::factory()->approved()->suspended()->create([
        'approved_by_id' => $approver->id,
        'suspended_by_id' => $suspender->id,
        'suspension_reason' => 'Unpaid fees.',
    ]);

    $data = AdminBusinessData::fromModel($business)->toArray();

    expect($data)->toMatchArray([
        'approved_by_name' => 'Approver',
        'suspended_by_name' => 'Suspender',
        'suspension_reason' => 'Unpaid fees.',
    ]);
});

test('a member lists their outlets by name and an invitation shows when it expired', function () {
    $business = Business::factory()->approved()->create();
    $zeta = Outlet::factory()->for($business)->approved()->create(['name' => 'Zeta']);
    $alpha = Outlet::factory()->for($business)->approved()->create(['name' => 'Alpha']);
    $member = Membership::factory()->cashier()->for($business)->withOutlets($zeta, $alpha)->create();
    $invitation = Invitation::factory()->for($business)->expired()->create();

    expect(collect(MemberData::fromModel($member)->toArray()['outlets'])->pluck('name')->all())->toBe(['Alpha', 'Zeta']);
    expect(InvitationData::fromModel($invitation)->toArray()['status'])->toBe('expired');
});

test('an activity lists each changed field with its old and new value, the actor and the reason', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin']);
    $business = Business::factory()->create(['name' => 'Before']);
    $this->actingAs($admin);

    app(AuditTrail::class)->as('renamed', 'Typo in the name.', fn () => $business->update(['name' => 'After']));

    $data = ActivityData::fromModel(Activity::forSubject($business)->where('event', 'renamed')->sole())->toArray();

    expect($data)->toMatchArray([
        'event' => 'renamed',
        'subject_type' => 'business',
        'causer_name' => 'Admin',
        'reason' => 'Typo in the name.',
        'changes' => [['field' => 'name', 'old' => 'Before', 'new' => 'After']],
    ]);
});

test('changed outlets are listed by name like any other change', function () {
    $business = Business::factory()->approved()->create();
    $gaya = Outlet::factory()->for($business)->approved()->create(['name' => 'Gaya Street']);
    $imago = Outlet::factory()->for($business)->approved()->create(['name' => 'Imago']);
    $cashier = Membership::factory()->cashier()->for($business)->withOutlets($gaya)->create();

    app(UpdateMemberAccess::class)->handle($cashier, MembershipRole::Cashier, [$imago->id]);

    $data = ActivityData::fromModel(Activity::forSubject($cashier)->where('event', 'outlets_changed')->sole())->toArray();

    expect($data['changes'])->toBe([['field' => 'outlets', 'old' => ['Gaya Street'], 'new' => ['Imago']]]);
    expect($data['properties'])->toBe([]);
});

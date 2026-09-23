<?php

use App\Actions\Outlets\CreateOutlet;
use App\Data\Forms\OutletDetailsData;
use App\Enums\OnboardingStatus;
use App\Models\Activity;
use App\Models\Business;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function outletDetails(array $overrides = []): OutletDetailsData
{
    return OutletDetailsData::validateAndCreate([
        'name' => 'Borneo Restaurant - Sabah Mall',
        'address_line_1' => 'Lot G-12, Sabah Mall',
        'city' => 'Kota Kinabalu',
        'state' => 'Sabah',
        'postcode' => '88000',
        'country_code' => 'my',
        ...$overrides,
    ]);
}

test('creates a draft outlet with an uppercase country code', function () {
    $business = Business::factory()->approved()->create();

    $outlet = app(CreateOutlet::class)->handle($business, outletDetails());

    expect($outlet->refresh()->onboarding_status)->toBe(OnboardingStatus::Draft);
    expect($outlet->country_code)->toBe('MY');
    expect($outlet->business_id)->toBe($business->id);
});

test('an admin can create an approved outlet for an approved business', function () {
    $admin = User::factory()->admin()->create();
    $business = Business::factory()->approved()->create();

    $outlet = app(CreateOutlet::class)->handle($business, outletDetails(), $admin);

    expect($outlet->onboarding_status)->toBe(OnboardingStatus::Approved);
    expect($outlet->approved_by_id)->toBe($admin->id);
    expect(Activity::forSubject($outlet)->sole()->event)->toBe('created_and_approved');
});

test('refuses to approve an outlet of a business that is not approved', function () {
    $admin = User::factory()->admin()->create();
    $business = Business::factory()->create();

    expect(fn () => app(CreateOutlet::class)->handle($business, outletDetails(), $admin))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('outlets', 0);
});

test('refuses to add an outlet to a suspended business', function () {
    $business = Business::factory()->approved()->suspended()->create();

    expect(fn () => app(CreateOutlet::class)->handle($business, outletDetails()))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('outlets', 0);
});

test('rejects a country code that is not two letters', function () {
    outletDetails(['country_code' => 'MYS']);
})->throws(ValidationException::class);

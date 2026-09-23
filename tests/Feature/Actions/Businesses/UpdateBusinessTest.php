<?php

use App\Actions\Businesses\UpdateBusiness;
use App\Data\Forms\BusinessDetailsData;
use App\Models\Activity;
use App\Models\Business;
use Illuminate\Validation\ValidationException;

test('saves business details at once and logs the old values', function () {
    $business = Business::factory()->approved()->create(['name' => 'Old Name', 'contact_email' => 'a@example.test']);

    app(UpdateBusiness::class)->handle($business, BusinessDetailsData::from([
        'name' => 'New Name',
        'contact_email' => 'a@example.test',
    ]));

    expect($business->refresh()->name)->toBe('New Name');
    expect(Activity::forSubject($business)->forEvent('updated')->sole()->attribute_changes->get('old'))
        ->toMatchArray(['name' => 'Old Name']);
});

test('refuses changes while the business is suspended or waiting for review', function (string $state) {
    $business = Business::factory()->{$state}()->create(['name' => 'Kept']);

    expect(fn () => app(UpdateBusiness::class)->handle($business, BusinessDetailsData::from([
        'name' => 'Changed',
        'contact_email' => 'a@example.test',
    ])))->toThrow(ValidationException::class);
    expect($business->refresh()->name)->toBe('Kept');
})->with(['pending', 'suspended']);

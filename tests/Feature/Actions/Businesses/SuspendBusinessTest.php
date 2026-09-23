<?php

use App\Actions\Businesses\SuspendBusiness;
use App\Models\Activity;
use App\Models\Business;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('suspends a business with a reason and reactivates it', function () {
    $admin = User::factory()->admin()->create();
    $business = Business::factory()->approved()->create();

    app(SuspendBusiness::class)->suspend($admin, $business, 'Reported for fake vouchers.');

    expect($business->refresh()->isSuspended())->toBeTrue();
    expect($business->suspended_by_id)->toBe($admin->id);

    app(SuspendBusiness::class)->reactivate($business);

    expect($business->refresh()->isSuspended())->toBeFalse();
    expect($business->suspension_reason)->toBeNull();
    expect(Activity::forSubject($business)->whereIn('event', ['suspended', 'reactivated'])->pluck('event')->all())
        ->toBe(['suspended', 'reactivated']);
});

test('refuses to suspend twice or reactivate an active business', function () {
    $admin = User::factory()->admin()->create();

    expect(fn () => app(SuspendBusiness::class)->suspend($admin, Business::factory()->suspended()->create(), 'Again'))
        ->toThrow(ValidationException::class);
    expect(fn () => app(SuspendBusiness::class)->reactivate(Business::factory()->create()))
        ->toThrow(ValidationException::class);
});

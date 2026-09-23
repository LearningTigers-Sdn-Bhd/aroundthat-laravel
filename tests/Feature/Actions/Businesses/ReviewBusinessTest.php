<?php

use App\Actions\Businesses\ReviewBusiness;
use App\Enums\OnboardingStatus;
use App\Models\Activity;
use App\Models\Business;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('approves a pending business', function () {
    $admin = User::factory()->admin()->create();
    $business = Business::factory()->pending()->create();

    app(ReviewBusiness::class)->approve($admin, $business);

    $business->refresh();
    expect($business->onboarding_status)->toBe(OnboardingStatus::Approved);
    expect($business->approved_by_id)->toBe($admin->id);
    expect(Activity::forSubject($business)->where('event', 'approved')->exists())->toBeTrue();
});

test('rejects a pending business with a reason the owner can read', function () {
    $business = Business::factory()->pending()->create();

    app(ReviewBusiness::class)->reject($business, 'The registration number does not match SSM.');

    $business->refresh();
    expect($business->onboarding_status)->toBe(OnboardingStatus::Rejected);
    expect($business->rejection_reason)->toBe('The registration number does not match SSM.');
    expect(Activity::forSubject($business)->where('event', 'rejected')->sole()->reason)
        ->toBe('The registration number does not match SSM.');
});

test('refuses to review a business that is not waiting for review', function (string $state) {
    $admin = User::factory()->admin()->create();
    $business = Business::factory()->{$state}()->create();

    expect(fn () => app(ReviewBusiness::class)->approve($admin, $business))->toThrow(ValidationException::class);
    expect(fn () => app(ReviewBusiness::class)->reject($business, 'No.'))->toThrow(ValidationException::class);
})->with(['approved', 'rejected']);

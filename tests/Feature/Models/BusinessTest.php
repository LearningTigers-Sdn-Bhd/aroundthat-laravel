<?php

use App\Enums\OnboardingStatus;
use App\Models\Business;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

test('new businesses start as drafts', function () {
    $business = Business::factory()->create();

    expect($business->refresh()->onboarding_status)->toBe(OnboardingStatus::Draft);
});

test('the database rejects an unknown onboarding status', function () {
    $business = Business::factory()->create();

    DB::table('businesses')->where('id', $business->id)->update(['onboarding_status' => 'published']);
})->throws(QueryException::class, 'businesses_onboarding_status_check');

test('approving a business logs the status change', function () {
    $business = Business::factory()->pending()->create();

    $business->forceFill(['onboarding_status' => OnboardingStatus::Approved, 'approved_at' => now()])->save();

    $activity = Activity::forSubject($business)->forEvent('updated')->sole();
    expect($activity->attribute_changes->all())->toEqual([
        'attributes' => ['onboarding_status' => 'approved'],
        'old' => ['onboarding_status' => 'pending'],
    ]);
});

test('only draft and rejected businesses can be submitted for review', function (OnboardingStatus $status, bool $canBeSubmitted) {
    expect($status->canBeSubmitted())->toBe($canBeSubmitted);
})->with([
    [OnboardingStatus::Draft, true],
    [OnboardingStatus::Rejected, true],
    [OnboardingStatus::Pending, false],
    [OnboardingStatus::Approved, false],
]);

<?php

use App\Enums\OnboardingStatus;
use App\Models\Activity;
use App\Models\Business;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;

test('changes made inside an action are logged once with the action name, reason, diff and actor', function () {
    $admin = User::factory()->admin()->create();
    $business = Business::factory()->pending()->create();
    $this->actingAs($admin);

    app(AuditTrail::class)->as('rejected', 'Registration number is missing.', fn () => $business->forceFill([
        'onboarding_status' => OnboardingStatus::Rejected,
        'rejection_reason' => 'Registration number is missing.',
    ])->save());

    $activity = Activity::forSubject($business)->where('event', '!=', 'created')->sole();
    expect($activity->event)->toBe('rejected');
    expect($activity->reason)->toBe('Registration number is missing.');
    expect($activity->causer->is($admin))->toBeTrue();
    expect($activity->ip_address)->toBe('127.0.0.1');
    expect($activity->subject_type)->toBe('business');
    expect($activity->attribute_changes->all())->toEqual([
        'old' => ['onboarding_status' => 'pending', 'rejection_reason' => null],
        'attributes' => ['onboarding_status' => 'rejected', 'rejection_reason' => 'Registration number is missing.'],
    ]);
});

test('changes made after an action are logged as plain updates again', function () {
    $business = Business::factory()->create(['name' => 'First']);

    app(AuditTrail::class)->as('renamed', null, fn () => $business->update(['name' => 'Second']));
    $business->update(['name' => 'Third']);

    expect(Activity::forSubject($business)->oldest('id')->pluck('event')->all())
        ->toBe(['created', 'renamed', 'updated']);
});

test('actions that change no fields can be recorded with a reason', function () {
    $business = Business::factory()->create();

    app(AuditTrail::class)->record($business, 'owner_invited', 'First owner', ['email' => 'owner@example.com']);

    $activity = Activity::forSubject($business)->where('event', 'owner_invited')->sole();
    expect($activity->reason)->toBe('First owner');
    expect($activity->properties->get('email'))->toBe('owner@example.com');
});

test('a failed action leaves no change and no log entry behind', function () {
    $business = Business::factory()->create(['name' => 'Before']);

    rescue(fn () => DB::transaction(function () use ($business) {
        app(AuditTrail::class)->as('renamed', null, fn () => $business->update(['name' => 'After']));

        throw new RuntimeException('Something failed after the change.');
    }), report: false);

    expect($business->refresh()->name)->toBe('Before');
    expect(Activity::forSubject($business)->where('event', 'renamed')->exists())->toBeFalse();
});

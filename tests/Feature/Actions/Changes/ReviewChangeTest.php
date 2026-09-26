<?php

use App\Actions\Changes\ReviewChange;
use App\Models\Activity;
use App\Models\Business;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Validation\ValidationException;

test('marks a content change reviewed by the admin', function () {
    $admin = User::factory()->admin()->create();
    $change = contentChange(Business::factory()->approved()->create(), ['name' => 'New Name']);

    app(ReviewChange::class)->review($admin, $change);

    expect($change->refresh()->reviewed_at)->not->toBeNull();
    expect($change->reviewed_by_id)->toBe($admin->id);
});

test('keeps the first review when a change is marked twice', function () {
    [$first, $second] = User::factory()->admin()->count(2)->create();
    $change = contentChange(Business::factory()->approved()->create(), ['name' => 'New Name']);

    app(ReviewChange::class)->review($first, $change);
    app(ReviewChange::class)->review($second, $change);

    expect($change->refresh()->reviewed_by_id)->toBe($first->id);
});

test('refuses to review activity outside the content log', function () {
    $business = Business::factory()->approved()->create();
    $activity = app(AuditTrail::class)->record($business, 'suspended', 'Unpaid fees.');

    app(ReviewChange::class)->review(User::factory()->admin()->create(), $activity);
})->throws(ValidationException::class, 'Only changes to public content are reviewed.');

test('marks many changes reviewed and skips other activity', function () {
    $admin = User::factory()->admin()->create();
    $business = Business::factory()->approved()->create();
    $first = contentChange($business, ['name' => 'Second']);
    $second = contentChange($business, ['name' => 'Third']);
    $other = app(AuditTrail::class)->record($business, 'suspended', 'Unpaid fees.');

    $count = app(ReviewChange::class)->reviewMany($admin, [$first->id, $second->id, $other->id]);

    expect($count)->toBe(2);
    expect(Activity::query()->content()->unreviewed()->exists())->toBeFalse();
    expect($other->refresh()->reviewed_at)->toBeNull();
});

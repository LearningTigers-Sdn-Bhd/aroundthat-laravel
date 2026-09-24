<?php

use App\Models\Activity;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('the feed lists content changes waiting for review by default', function () {
    $outlet = Outlet::factory()->approved()->create(['name' => 'Kedai Aminah']);
    $waiting = contentChange($outlet, ['summary' => 'Fresh bread daily.'], 'public_profile_changed');
    contentChange($outlet, ['summary' => 'Reviewed already.'], 'public_profile_changed')
        ->forceFill(['reviewed_at' => now()])->save();
    $outlet->update(['contact_phone' => '+60123456789']);

    $this->actingAs($this->admin)
        ->get(route('admin.changes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/changes/index')
            ->has('changes.data', 1)
            ->where('changes.data.0.activity.id', $waiting->id)
            ->where('changes.data.0.subject.name', 'Kedai Aminah')
            ->where('changes.data.0.subject.business_name', $outlet->business->name));
});

test('the feed filters by status and searches by business name', function () {
    $business = Business::factory()->approved()->create(['name' => 'Roti Bakar Sdn Bhd']);
    $reviewed = contentChange(Outlet::factory()->for($business)->approved()->create(), ['summary' => 'Toast.'], 'public_profile_changed');
    $reviewed->forceFill(['reviewed_at' => now()])->save();
    contentChange(Outlet::factory()->approved()->create(), ['summary' => 'Other.'], 'public_profile_changed')
        ->forceFill(['reviewed_at' => now()])->save();

    $this->actingAs($this->admin)
        ->get(route('admin.changes.index', ['filter' => ['status' => 'reviewed', 'search' => 'roti bakar']]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('changes.data', 1)
            ->where('changes.data.0.activity.id', $reviewed->id));
});

test('an admin marks one change and then all shown changes reviewed', function () {
    $business = Business::factory()->approved()->create();
    $one = contentChange($business, ['name' => 'Second']);
    $two = contentChange($business, ['name' => 'Third']);
    $three = contentChange($business, ['name' => 'Fourth']);

    $this->actingAs($this->admin)
        ->post(route('admin.changes.review', $one))
        ->assertSessionHasNoErrors();
    $this->post(route('admin.changes.review-many'), ['ids' => [$two->id, $three->id]])
        ->assertSessionHasNoErrors();

    expect(Activity::query()->content()->unreviewed()->exists())->toBeFalse();
    expect($one->refresh()->reviewed_by_id)->toBe($this->admin->id);
});

test('the dashboard counts changes waiting for review', function () {
    contentChange(Business::factory()->approved()->create(), ['name' => 'Second']);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('unreviewedChangeCount', 1));
});

test('only admins see and review changes', function () {
    $change = contentChange(Business::factory()->approved()->create(), ['name' => 'Second']);
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.changes.index'))->assertForbidden();
    $this->post(route('admin.changes.review', $change))->assertForbidden();
});

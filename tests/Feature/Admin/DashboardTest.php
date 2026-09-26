<?php

use App\Enums\OnboardingStatus;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the dashboard lists the oldest items waiting in each column', function () {
    $newer = Business::factory()->create(['onboarding_status' => OnboardingStatus::Pending, 'submitted_at' => now()]);
    $older = Business::factory()->create(['onboarding_status' => OnboardingStatus::Pending, 'submitted_at' => now()->subDay()]);
    $outlet = Outlet::factory()->create(['onboarding_status' => OnboardingStatus::Pending, 'submitted_at' => now()]);
    $tag = Tag::factory()->pendingFrom()->create();
    $changed = Business::factory()->approved()->create();
    contentChange($changed, ['name' => 'Kedai Aminah']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('pendingBusinesses.0.id', $older->id)
            ->where('pendingBusinesses.1.id', $newer->id)
            ->where('pendingOutletCount', 1)
            ->where('pendingOutlets.0.id', $outlet->id)
            ->where('pendingTags.0.id', $tag->id)
            ->where('unreviewedChanges.0.subject.id', $changed->id)
            ->where('unreviewedChanges.0.subject.name', 'Kedai Aminah'));
});

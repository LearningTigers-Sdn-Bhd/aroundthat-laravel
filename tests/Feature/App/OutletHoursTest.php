<?php

use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use Inertia\Testing\AssertableInertia as Assert;

test('the owner sees and saves the hours', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
    $outlet = Outlet::factory()->for($owner->business)->approved()->create();

    $this->actingAs($owner->user)
        ->get(route('outlets.hours.edit', $outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/hours')
            ->where('hours.regular_hours.1', [])
            ->where('hours.timezone', 'Asia/Kuala_Lumpur'));

    $this->put(route('outlets.hours.update', $outlet), [
        'regular_hours' => ['1' => [['opens' => '09:00', 'closes' => '12:00'], ['opens' => '11:00', 'closes' => '15:00']]],
    ])->assertSessionHasErrors('regular_hours.1');

    $this->put(route('outlets.hours.update', $outlet), [
        'regular_hours' => ['1' => [['opens' => '09:00', 'closes' => '17:00']]],
    ])->assertSessionHasNoErrors();

    expect($outlet->refresh()->regular_hours['1'])->toBe([['opens' => '09:00', 'closes' => '17:00']]);
});

test('managers cannot change the hours', function () {
    $manager = Membership::factory()->manager()->create();
    $outlet = Outlet::factory()->for($manager->business)->create();

    $this->actingAs($manager->user)->get(route('outlets.hours.edit', $outlet))->assertForbidden();
    $this->put(route('outlets.hours.update', $outlet), [])->assertForbidden();
});

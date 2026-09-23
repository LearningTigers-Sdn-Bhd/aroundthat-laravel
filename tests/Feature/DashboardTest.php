<?php

use App\Models\Membership;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('members can visit the dashboard', function () {
    $membership = Membership::factory()->create();
    $this->actingAs($membership->user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

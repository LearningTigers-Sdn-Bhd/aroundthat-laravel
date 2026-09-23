<?php

use App\Models\User;

test('suspended users cannot sign in with the correct password', function () {
    $user = User::factory()->suspended()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email' => 'This account is suspended. Contact support if you think this is a mistake.']);
    $this->assertGuest();
    expect($user->refresh()->last_login_at)->toBeNull();
});

test('users suspended while signed in are signed out on their next request', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $user->forceFill(['suspended_at' => now()])->save();

    $response = $this->get(route('dashboard'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('signing in records the last login time', function () {
    $this->freezeSecond();
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    expect($user->refresh()->last_login_at->equalTo(now()))->toBeTrue();
});

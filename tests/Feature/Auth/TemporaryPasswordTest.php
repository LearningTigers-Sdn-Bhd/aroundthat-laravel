<?php

use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('users with a temporary password are sent to the security page', function () {
    $user = User::factory()->mustChangePassword()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('security.edit'));
});

test('users with a temporary password can open the security page', function () {
    $user = User::factory()->mustChangePassword()->create();

    $response = $this
        ->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'));

    $response->assertOk();
});

test('changing the password clears the temporary password requirement', function () {
    $user = User::factory()->mustChangePassword()->create();
    Membership::factory()->for($user)->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response->assertSessionHasNoErrors();
    $user->refresh();
    expect($user->must_change_password)->toBeFalse();
    expect(Hash::check('new-password', $user->password))->toBeTrue();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('a failed password change keeps the temporary password requirement', function () {
    $user = User::factory()->mustChangePassword()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response->assertSessionHasErrors('current_password');
    expect($user->refresh()->must_change_password)->toBeTrue();
});

test('resetting a forgotten password clears the temporary password requirement', function () {
    $user = User::factory()->mustChangePassword()->create();

    $response = $this->post(route('password.update'), [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasNoErrors();
    expect($user->refresh()->must_change_password)->toBeFalse();
});

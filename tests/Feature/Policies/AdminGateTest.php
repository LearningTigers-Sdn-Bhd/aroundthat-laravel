<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('only active system admins pass the admin gate', function (Closure $makeUser, bool $allowed) {
    expect(Gate::forUser($makeUser())->allows('admin'))->toBe($allowed);
})->with([
    'admin' => [fn () => User::factory()->admin()->create(), true],
    'suspended admin' => [fn () => User::factory()->admin()->suspended()->create(), false],
    'regular user' => [fn () => User::factory()->create(), false],
]);

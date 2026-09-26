<?php

use App\Models\Business;
use App\Models\Outlet;

test('outlets get a slug from their name, numbered when it is taken', function () {
    $first = Outlet::factory()->create(['name' => 'Kopi Corner']);
    $second = Outlet::factory()->create(['name' => 'Kopi  corner!']);

    expect($first->slug)->toBe('kopi-corner');
    expect($second->slug)->toBe('kopi-corner-2');
});

test('names Str::slug cannot transliterate keep their own letters', function () {
    expect(Business::factory()->create(['name' => '清真 餐厅'])->slug)->toBe('清真-餐厅');
    expect(Business::factory()->create(['name' => 'முருகன் கடை'])->slug)->toBe('முருகன்-கடை');
});

test('a name with no letter or digit gets a random slug', function () {
    expect(Outlet::factory()->create(['name' => '🍜🍜'])->slug)->toMatch('/^[a-z0-9]{8}$/');
});

test('the slug stays the same when the outlet is renamed', function () {
    $outlet = Outlet::factory()->create(['name' => 'Kopi Corner']);

    $outlet->update(['name' => 'Kopi Corner Level 2']);

    expect($outlet->refresh()->slug)->toBe('kopi-corner');
});

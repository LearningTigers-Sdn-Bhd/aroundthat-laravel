<?php

use App\Actions\Outlets\SetHostOutlet;
use App\Models\Activity;
use App\Models\Outlet;
use Illuminate\Validation\ValidationException;

test('places an outlet inside an approved host of another business', function () {
    $mall = Outlet::factory()->approved()->create();
    $restaurant = Outlet::factory()->create();

    app(SetHostOutlet::class)->assign($restaurant, $mall);

    expect($restaurant->refresh()->host_outlet_id)->toBe($mall->id);
    expect(Activity::forSubject($restaurant)->where('event', 'host_assigned')->exists())->toBeTrue();
});

test('clears a host', function () {
    $restaurant = Outlet::factory()->create(['host_outlet_id' => Outlet::factory()->approved()->create()->id]);

    app(SetHostOutlet::class)->clear($restaurant);

    expect($restaurant->refresh()->host_outlet_id)->toBeNull();
});

test('refuses a host that would place an outlet inside itself', function () {
    $top = Outlet::factory()->approved()->create();
    $middle = Outlet::factory()->approved()->create(['host_outlet_id' => $top->id]);
    $bottom = Outlet::factory()->approved()->create(['host_outlet_id' => $middle->id]);

    expect(fn () => app(SetHostOutlet::class)->assign($top, $bottom))->toThrow(ValidationException::class);
    expect(fn () => app(SetHostOutlet::class)->assign($top, $top))->toThrow(ValidationException::class);
    expect($top->refresh()->host_outlet_id)->toBeNull();
});

test('refuses a host that is not approved or is archived', function (Closure $makeHost) {
    $outlet = Outlet::factory()->create();

    expect(fn () => app(SetHostOutlet::class)->assign($outlet, $makeHost()))->toThrow(ValidationException::class);
    expect($outlet->refresh()->host_outlet_id)->toBeNull();
})->with([
    'draft' => [fn () => Outlet::factory()->create()],
    'archived' => [fn () => Outlet::factory()->approved()->archived()->create()],
]);

test('refuses to give an archived outlet a host', function () {
    $outlet = Outlet::factory()->archived()->create();

    expect(fn () => app(SetHostOutlet::class)->assign($outlet, Outlet::factory()->approved()->create()))
        ->toThrow(ValidationException::class);
});

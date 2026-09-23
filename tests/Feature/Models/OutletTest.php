<?php

use App\Models\Business;
use App\Models\Outlet;
use Illuminate\Database\QueryException;
use Spatie\Activitylog\Models\Activity;

test('operational outlets are approved, active, unarchived and belong to an approved active business', function () {
    $business = Business::factory()->approved()->create();
    $operational = Outlet::factory()->for($business)->approved()->create();
    Outlet::factory()->for($business)->pending()->create();
    Outlet::factory()->for($business)->approved()->suspended()->create();
    Outlet::factory()->for($business)->approved()->archived()->create();
    Outlet::factory()->for(Business::factory()->pending())->approved()->create();
    Outlet::factory()->for(Business::factory()->approved()->suspended())->approved()->create();

    $ids = Outlet::operational()->pluck('id');

    expect($ids->all())->toBe([$operational->id]);
    expect(Outlet::all()->filter->isOperational()->pluck('id')->all())->toBe([$operational->id]);
});

test('the database rejects an outlet hosted inside itself', function () {
    $outlet = Outlet::factory()->create();

    $outlet->forceFill(['host_outlet_id' => $outlet->id])->save();
})->throws(QueryException::class, 'outlets_host_not_self_check');

test('the database rejects a country code that is not two capital letters', function () {
    Outlet::factory()->create(['country_code' => 'my']);
})->throws(QueryException::class, 'outlets_country_code_check');

test('a business with outlets cannot be deleted', function () {
    $outlet = Outlet::factory()->create();

    $outlet->business->delete();
})->throws(QueryException::class, 'outlets_business_id_foreign');

test('updating an outlet logs only the changed fields with their old values', function () {
    $outlet = Outlet::factory()->create(['name' => 'Old Name', 'city' => 'Kota Kinabalu']);

    $outlet->update(['name' => 'New Name', 'city' => 'Kota Kinabalu']);

    $activity = Activity::forSubject($outlet)->forEvent('updated')->sole();
    expect($activity->attribute_changes->all())->toEqual([
        'attributes' => ['name' => 'New Name'],
        'old' => ['name' => 'Old Name'],
    ]);
});

test('saving an outlet without changes does not log an update', function () {
    $outlet = Outlet::factory()->create();

    $outlet->update(['name' => $outlet->name]);

    expect(Activity::forSubject($outlet)->forEvent('updated')->exists())->toBeFalse();
});

<?php

use App\Support\GoogleMapsLink;

test('reads coordinates from a Google Maps link', function (string $link, float $latitude, float $longitude) {
    expect(GoogleMapsLink::coordinates($link))->toBe(['latitude' => $latitude, 'longitude' => $longitude]);
})->with([
    'place pin wins over the map centre' => ['https://www.google.com/maps/place/Kopi/@5.9804,116.0735,17z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!8m2!3d5.9812345!4d116.0741234', 5.981235, 116.074123],
    'map centre' => ['https://www.google.com.my/maps/@1.5535,110.3593,17z', 1.5535, 110.3593],
    'query pair' => ['https://maps.google.com/?q=loc:1.5,110.3', 1.5, 110.3],
    'negative coordinates' => ['https://www.google.com/maps?ll=-33.8688,151.2093', -33.8688, 151.2093],
]);

test('refuses links it cannot read', function (string $link, string $message) {
    expect(fn () => GoogleMapsLink::coordinates($link))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'not a link' => ['kopi corner', 'must start with http'],
    'shortened' => ['https://maps.app.goo.gl/abc123', 'shortened link'],
    'another site' => ['https://www.openstreetmap.org/#map=17/1.5/110.3', 'must be a Google Maps link'],
    'no coordinates' => ['https://www.google.com/maps/search/kopi', 'does not carry coordinates'],
    'out of range' => ['https://www.google.com/maps/@95.1,110.3,17z', 'outside the valid range'],
]);

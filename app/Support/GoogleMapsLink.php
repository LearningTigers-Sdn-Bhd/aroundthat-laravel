<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Reads the coordinates out of a Google Maps link, so an owner can paste the link instead of typing a latitude and
 * a longitude. Nothing is fetched: a shortened maps.app.goo.gl link carries no coordinates, so it is refused.
 */
class GoogleMapsLink
{
    protected const string NUMBER = '-?\d{1,3}(?:\.\d+)?';

    /**
     * Parameters that carry a literal "lat,lng" pair. `q` may arrive as `q=loc:1.5,110.3`.
     *
     * @var list<string>
     */
    protected const array PAIR_PARAMETERS = ['q', 'query', 'destination', 'daddr', 'saddr', 'll', 'sll', 'center', 'viewpoint'];

    /**
     * @var list<string>
     */
    protected const array SHORT_HOSTS = ['maps.app.goo.gl', 'goo.gl', 'g.co'];

    /**
     * @return array{latitude: float, longitude: float}
     *
     * @throws InvalidArgumentException With a message an owner can act on.
     */
    public static function coordinates(string $link): array
    {
        $parts = parse_url(trim($link)) ?: [];
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = preg_replace('/^www\./', '', strtolower($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new InvalidArgumentException(__('The Google Maps link must start with http:// or https://.'));
        }

        if (in_array($host, self::SHORT_HOSTS, true)) {
            throw new InvalidArgumentException(__('This is a shortened link. Open it in Google Maps, then copy the full link from the address bar.'));
        }

        if (! preg_match('/^(?:[a-z0-9-]+\.)*google(?:\.[a-z]{2,3}){1,2}$/', (string) $host)) {
            throw new InvalidArgumentException(__('The link must be a Google Maps link.'));
        }

        $pair = self::pinOf($link) ?? self::pairFromParameters($parts) ?? self::centreOf($link);

        if ($pair === null) {
            throw new InvalidArgumentException(__('This link does not carry coordinates. Open the place in Google Maps, then copy the link from the address bar.'));
        }

        [$latitude, $longitude] = [round((float) $pair[0], 6), round((float) $pair[1], 6)];

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException(__('The link carries coordinates outside the valid range.'));
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }

    /**
     * The place pin Google resolved. It is more exact than the map centre, so it wins.
     *
     * @return array{0: string, 1: string}|null
     */
    protected static function pinOf(string $link): ?array
    {
        return preg_match('/!3d('.self::NUMBER.')!4d('.self::NUMBER.')/', $link, $match) ? [$match[1], $match[2]] : null;
    }

    /**
     * The map centre in the path, as in `/@1.5535,110.3593,17z`.
     *
     * @return array{0: string, 1: string}|null
     */
    protected static function centreOf(string $link): ?array
    {
        return preg_match('#/@('.self::NUMBER.'),\s*('.self::NUMBER.')#', $link, $match) ? [$match[1], $match[2]] : null;
    }

    /**
     * Google puts the pair in the fragment on some map views, so both the query and the fragment are read.
     *
     * @param  array<string, mixed>  $parts
     * @return array{0: string, 1: string}|null
     */
    protected static function pairFromParameters(array $parts): ?array
    {
        foreach (['query', 'fragment'] as $source) {
            parse_str((string) ($parts[$source] ?? ''), $values);

            foreach (self::PAIR_PARAMETERS as $key) {
                $value = preg_replace('/^loc:/', '', trim((string) (is_string($values[$key] ?? null) ? $values[$key] : '')));

                if (preg_match('/^('.self::NUMBER.'),\s*('.self::NUMBER.')$/', (string) $value, $match)) {
                    return [$match[1], $match[2]];
                }
            }
        }

        return null;
    }
}

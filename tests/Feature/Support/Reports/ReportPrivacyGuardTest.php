<?php

use App\Support\Reports\ReportPrivacyGuard;

function outletRow(string $key, int $redemptions, bool $protected): array
{
    return ['key' => $key, 'label' => $key, 'used' => $redemptions, 'privacy_count' => $redemptions, 'protected' => $protected];
}

function hiddenKeys(array $result): array
{
    return array_column(array_filter($result['rows'], fn (array $row): bool => $row['hidden']), 'key');
}

test('a small sponsored outlet is hidden with the next smallest sponsored outlet', function () {
    $result = (new ReportPrivacyGuard)->protect([
        outletRow('own', 2, false),
        outletRow('small', 3, true),
        outletRow('medium', 9, true),
        outletRow('large', 40, true),
    ], ['used']);

    expect(hiddenKeys($result))->toBe(['small', 'medium'])
        ->and($result['summaryHidden'])->toBeFalse();
});

test('a hidden row keeps its name and loses its numbers', function () {
    $result = (new ReportPrivacyGuard)->protect([outletRow('small', 1, true), outletRow('other', 2, true)], ['used']);

    expect($result['rows'][0])->toBe(['key' => 'small', 'label' => 'small', 'used' => null, 'hidden' => true]);
});

test('own outlets and sponsored outlets with five or more redemptions are shown exactly', function () {
    $result = (new ReportPrivacyGuard)->protect([
        outletRow('own', 1, false),
        outletRow('sponsored', 5, true),
        outletRow('unused', 0, true),
    ], ['used']);

    expect(hiddenKeys($result))->toBe([])
        ->and(array_column($result['rows'], 'used'))->toBe([1, 5, 0]);
});

test('two small sponsored outlets hide each other and nothing more', function () {
    $result = (new ReportPrivacyGuard)->protect([
        outletRow('first', 1, true),
        outletRow('second', 4, true),
        outletRow('large', 30, true),
    ], ['used']);

    expect(hiddenKeys($result))->toBe(['first', 'second'])
        ->and($result['summaryHidden'])->toBeFalse();
});

test('the summary is hidden when a lone small sponsored outlet has no other to hide with it', function () {
    $result = (new ReportPrivacyGuard)->protect([outletRow('own', 12, false), outletRow('small', 2, true)], ['used']);

    expect(hiddenKeys($result))->toBe(['small'])
        ->and($result['summaryHidden'])->toBeTrue();
});

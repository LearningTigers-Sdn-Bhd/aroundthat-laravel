<?php

use App\Enums\Ability;
use App\Enums\MembershipRole;

test('each role grants exactly its abilities', function (MembershipRole $role, array $granted) {
    $actual = collect(Ability::cases())->filter(fn (Ability $ability) => $role->can($ability))->values()->all();

    expect($actual)->toBe($granted);
})->with([
    'owner' => [MembershipRole::Owner, Ability::cases()],
    'manager' => [MembershipRole::Manager, [
        Ability::Scan,
        Ability::ViewTodayActivity,
        Ability::ViewReports,
        Ability::ViewStatements,
        Ability::ManageOffers,
    ]],
    'cashier' => [MembershipRole::Cashier, [
        Ability::Scan,
        Ability::ViewTodayActivity,
    ]],
]);

test('only owners cover every outlet', function (MembershipRole $role, bool $coversAll) {
    expect($role->coversAllOutlets())->toBe($coversAll);
})->with([
    [MembershipRole::Owner, true],
    [MembershipRole::Manager, false],
    [MembershipRole::Cashier, false],
]);

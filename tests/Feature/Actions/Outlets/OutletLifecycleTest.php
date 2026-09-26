<?php

use App\Actions\Outlets\ChangeOutletStatus;
use App\Actions\Outlets\ReviewOutlet;
use App\Actions\Outlets\SubmitOutlet;
use App\Actions\Outlets\UpdateOutlet;
use App\Data\Forms\OutletDetailsData;
use App\Enums\OnboardingStatus;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function approvedBusinessOutlet(): Outlet
{
    return Outlet::factory()->for(Business::factory()->approved())->create();
}

describe('update', function () {
    test('saves outlet details at once', function () {
        $outlet = approvedBusinessOutlet();

        app(UpdateOutlet::class)->handle($outlet, OutletDetailsData::from([
            ...$outlet->only(['address_line_1', 'city', 'state', 'postcode']),
            'name' => 'Renamed Outlet',
        ]));

        expect($outlet->refresh()->name)->toBe('Renamed Outlet');
    });

    test('refuses changes while archived, suspended, pending or the business is suspended', function (Closure $makeOutlet) {
        $outlet = $makeOutlet();
        $name = $outlet->name;

        expect(fn () => app(UpdateOutlet::class)->handle($outlet, OutletDetailsData::from([
            ...$outlet->only(['address_line_1', 'city', 'state', 'postcode']),
            'name' => 'Renamed Outlet',
        ])))->toThrow(ValidationException::class);
        expect($outlet->refresh()->name)->toBe($name);
    })->with([
        'archived' => [fn () => Outlet::factory()->archived()->create()],
        'suspended' => [fn () => Outlet::factory()->suspended()->create()],
        'pending' => [fn () => Outlet::factory()->pending()->create()],
        'business suspended' => [fn () => Outlet::factory()->for(Business::factory()->suspended())->create()],
    ]);
});

describe('submit', function () {
    test('submits a draft outlet of an approved business', function () {
        $outlet = approvedBusinessOutlet();

        app(SubmitOutlet::class)->handle($outlet);

        expect($outlet->refresh()->onboarding_status)->toBe(OnboardingStatus::Pending);
    });

    test('refuses while the business is not approved', function () {
        $outlet = Outlet::factory()->create();

        expect(fn () => app(SubmitOutlet::class)->handle($outlet))->toThrow(ValidationException::class);
        expect($outlet->refresh()->onboarding_status)->toBe(OnboardingStatus::Draft);
    });
});

describe('review', function () {
    test('approves a pending outlet', function () {
        $admin = User::factory()->admin()->create();
        $outlet = Outlet::factory()->for(Business::factory()->approved())->pending()->create();

        app(ReviewOutlet::class)->approve($admin, $outlet);

        expect($outlet->refresh()->onboarding_status)->toBe(OnboardingStatus::Approved);
        expect($outlet->approved_by_id)->toBe($admin->id);
    });

    test('rejects a pending outlet with a reason', function () {
        $outlet = Outlet::factory()->pending()->create();

        app(ReviewOutlet::class)->reject($outlet, 'The address is incomplete.');

        expect($outlet->refresh()->onboarding_status)->toBe(OnboardingStatus::Rejected);
        expect($outlet->rejection_reason)->toBe('The address is incomplete.');
    });

    test('refuses to approve an outlet whose business is not approved', function () {
        $admin = User::factory()->admin()->create();
        $outlet = Outlet::factory()->pending()->create();

        expect(fn () => app(ReviewOutlet::class)->approve($admin, $outlet))->toThrow(ValidationException::class);
        expect($outlet->refresh()->onboarding_status)->toBe(OnboardingStatus::Pending);
    });

    test('refuses to review an outlet that is not pending', function () {
        $outlet = Outlet::factory()->approved()->create();

        expect(fn () => app(ReviewOutlet::class)->reject($outlet, 'No.'))->toThrow(ValidationException::class);
    });
});

describe('status', function () {
    test('suspends and reactivates an outlet with the reason logged', function () {
        $admin = User::factory()->admin()->create();
        $outlet = approvedBusinessOutlet();

        app(ChangeOutletStatus::class)->suspend($admin, $outlet, 'Health inspection failed.');
        expect($outlet->refresh()->isSuspended())->toBeTrue();

        app(ChangeOutletStatus::class)->reactivate($outlet);
        expect($outlet->refresh()->isSuspended())->toBeFalse();
        expect(Activity::forSubject($outlet)->where('event', 'suspended')->sole()->reason)->toBe('Health inspection failed.');
    });

    test('archives and restores an outlet', function () {
        $outlet = approvedBusinessOutlet();

        app(ChangeOutletStatus::class)->archive($outlet);
        expect($outlet->refresh()->isArchived())->toBeTrue();

        app(ChangeOutletStatus::class)->restore($outlet);
        expect($outlet->refresh()->isArchived())->toBeFalse();
    });

    test('refuses to suspend an archived outlet', function () {
        $admin = User::factory()->admin()->create();
        $outlet = Outlet::factory()->archived()->create();

        expect(fn () => app(ChangeOutletStatus::class)->suspend($admin, $outlet, 'Too late.'))
            ->toThrow(ValidationException::class);
        expect($outlet->refresh()->isSuspended())->toBeFalse();
    });

    test('refuses to reactivate an archived outlet', function () {
        $outlet = Outlet::factory()->suspended()->archived()->create();

        expect(fn () => app(ChangeOutletStatus::class)->reactivate($outlet))->toThrow(ValidationException::class);
        expect($outlet->refresh()->isSuspended())->toBeTrue();
    });
});

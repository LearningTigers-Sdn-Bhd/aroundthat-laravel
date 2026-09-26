<?php

use App\Actions\Businesses\SubmitBusiness;
use App\Enums\OnboardingStatus;
use App\Models\Business;
use Illuminate\Validation\ValidationException;

function completeBusiness(): array
{
    return ['registered_name' => 'Borneo Restaurant Sdn Bhd', 'registration_number' => '202401234567'];
}

test('submits a complete draft business for review', function () {
    $this->freezeSecond();
    $business = Business::factory()->create(completeBusiness());

    app(SubmitBusiness::class)->handle($business);

    $business->refresh();
    expect($business->onboarding_status)->toBe(OnboardingStatus::Pending);
    expect($business->submitted_at->equalTo(now()))->toBeTrue();
});

test('resubmitting a rejected business clears the old rejection reason', function () {
    $business = Business::factory()->rejected()->create(completeBusiness());

    app(SubmitBusiness::class)->handle($business);

    expect($business->refresh()->rejection_reason)->toBeNull();
});

test('lists the missing legal details', function () {
    $business = Business::factory()->create();

    expect(fn () => app(SubmitBusiness::class)->handle($business))->toThrow(
        fn (ValidationException $exception) => expect(array_keys($exception->errors()))
            ->toBe(['registered_name', 'registration_number']),
    );

    expect($business->refresh()->onboarding_status)->toBe(OnboardingStatus::Draft);
});

test('refuses a business that is pending, approved or suspended', function (Closure $makeBusiness) {
    $business = $makeBusiness();
    $status = $business->onboarding_status;

    expect(fn () => app(SubmitBusiness::class)->handle($business))->toThrow(ValidationException::class);
    expect($business->refresh()->onboarding_status)->toBe($status);
})->with([
    'pending' => [fn () => Business::factory()->pending()->create(completeBusiness())],
    'approved' => [fn () => Business::factory()->approved()->create(completeBusiness())],
    'suspended' => [fn () => Business::factory()->suspended()->create(completeBusiness())],
]);

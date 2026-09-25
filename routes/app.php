<?php

use App\Http\Controllers\App\BusinessController;
use App\Http\Controllers\App\BusinessPublicProfileController;
use App\Http\Controllers\App\CounterController;
use App\Http\Controllers\App\InvitationController;
use App\Http\Controllers\App\OfferController;
use App\Http\Controllers\App\OfferOutletController;
use App\Http\Controllers\App\OfferStatusController;
use App\Http\Controllers\App\OutletController;
use App\Http\Controllers\App\OutletHoursController;
use App\Http\Controllers\App\OutletLinksController;
use App\Http\Controllers\App\OutletListingController;
use App\Http\Controllers\App\OutletLocationController;
use App\Http\Controllers\App\OutletPhotoController;
use App\Http\Controllers\App\OutletPreviewController;
use App\Http\Controllers\App\OutletPublicProfileController;
use App\Http\Controllers\App\OutletStatusController;
use App\Http\Controllers\App\ReportController;
use App\Http\Controllers\App\StaffController;
use App\Http\Controllers\App\VoucherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'business'])->prefix('app')->group(function () {
    Route::inertia('/', 'dashboard')->name('dashboard');

    Route::get('business', [BusinessController::class, 'edit'])->name('business.edit');
    Route::put('business', [BusinessController::class, 'update'])->name('business.update');
    Route::post('business/submit', [BusinessController::class, 'submit'])->name('business.submit');
    Route::controller(BusinessPublicProfileController::class)->prefix('business')->name('business.')->group(function () {
        Route::put('public', 'update')->name('public.update');
        Route::post('logo', 'storeLogo')->name('logo.store');
        Route::delete('logo', 'destroyLogo')->name('logo.destroy');
    });

    Route::resource('outlets', OutletController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::controller(OutletStatusController::class)->prefix('outlets/{outlet}')->name('outlets.')->group(function () {
        Route::post('submit', 'submit')->name('submit');
        Route::post('archive', 'archive')->name('archive');
        Route::post('restore', 'restore')->name('restore');
    });

    Route::controller(OutletPublicProfileController::class)->prefix('outlets/{outlet}/public')->name('outlets.public.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

    Route::controller(OutletLocationController::class)->prefix('outlets/{outlet}/location')->name('outlets.location.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

    Route::controller(OutletLinksController::class)->prefix('outlets/{outlet}/links')->name('outlets.links.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

    Route::put('outlets/{outlet}/listing', [OutletListingController::class, 'update'])->name('outlets.listing.update');

    Route::get('outlets/{outlet}/preview', OutletPreviewController::class)->name('outlets.preview');

    Route::controller(OutletPhotoController::class)->prefix('outlets/{outlet}/photos')->name('outlets.photos.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('order', 'reorder')->name('reorder');
        Route::put('{image}', 'update')->name('update');
        Route::delete('{image}', 'destroy')->name('destroy');
    });

    Route::controller(OutletHoursController::class)->prefix('outlets/{outlet}/hours')->name('outlets.hours.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

    Route::resource('offers', OfferController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::controller(OfferStatusController::class)->prefix('offers/{offer}')->name('offers.')->group(function () {
        Route::post('activate', 'activate')->name('activate');
        Route::post('pause', 'pause')->name('pause');
    });

    Route::put('offers/{offer}/outlets', [OfferOutletController::class, 'update'])->name('offers.outlets.update');

    Route::controller(VoucherController::class)->prefix('offers/{offer}/vouchers')->name('offers.vouchers.')->scopeBindings()->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('{voucher}/reveal', 'reveal')->middleware('throttle:6,1')->name('reveal');
        Route::post('{voucher}/void', 'void')->name('void');
    });

    Route::controller(CounterController::class)->prefix('counter')->name('counter.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::middleware('throttle:counter')->group(function () {
            Route::post('check', 'check')->name('check');
            Route::post('redemptions', 'redeem')->name('redeem');
            Route::post('redemptions/{redemption}/cancel', 'cancel')->name('cancel');
        });
    });

    Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('{report}', 'show')->name('show');
        Route::get('{report}/export', 'export')->middleware('throttle:30,1')->name('export');
    });

    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');

        Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
        Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

        Route::put('{membership}', [StaffController::class, 'update'])->name('update');
        Route::post('{membership}/suspend', [StaffController::class, 'suspend'])->name('suspend');
        Route::post('{membership}/reactivate', [StaffController::class, 'reactivate'])->name('reactivate');
        Route::delete('{membership}', [StaffController::class, 'destroy'])->name('destroy');
    });
});

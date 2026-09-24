<?php

use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\BusinessStatusController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChangeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\OutletController;
use App\Http\Controllers\Admin\OutletHostController;
use App\Http\Controllers\Admin\OutletStatusController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('businesses', BusinessController::class)->only(['index', 'create', 'store', 'show']);

    Route::controller(BusinessStatusController::class)->prefix('businesses/{business}')->name('businesses.')->group(function () {
        Route::post('approve', 'approve')->name('approve');
        Route::post('reject', 'reject')->name('reject');
        Route::post('suspend', 'suspend')->name('suspend');
        Route::post('reactivate', 'reactivate')->name('reactivate');
    });

    Route::resource('businesses.outlets', OutletController::class)->only(['create', 'store', 'show'])->shallow();

    Route::controller(OutletStatusController::class)->prefix('outlets/{outlet}')->name('outlets.')->group(function () {
        Route::post('approve', 'approve')->name('approve');
        Route::post('reject', 'reject')->name('reject');
        Route::post('suspend', 'suspend')->name('suspend');
        Route::post('reactivate', 'reactivate')->name('reactivate');
        Route::post('archive', 'archive')->name('archive');
        Route::post('restore', 'restore')->name('restore');
        Route::post('hide', 'hide')->name('hide');
        Route::post('unhide', 'unhide')->name('unhide');
    });

    Route::put('outlets/{outlet}/host', [OutletHostController::class, 'update'])->name('outlets.host.update');
    Route::delete('outlets/{outlet}/host', [OutletHostController::class, 'destroy'])->name('outlets.host.destroy');

    Route::resource('users', UserController::class)->only(['index', 'show']);
    Route::post('users/{user}/suspend', [UserStatusController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/reactivate', [UserStatusController::class, 'reactivate'])->name('users.reactivate');

    Route::put('categories/order', [CategoryController::class, 'reorder'])->name('categories.reorder');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update']);

    Route::resource('tags', TagController::class)->only(['index', 'store', 'update']);
    Route::controller(TagController::class)->prefix('tags/{tag}')->name('tags.')->group(function () {
        Route::post('approve', 'approve')->name('approve');
        Route::post('reject', 'reject')->name('reject');
        Route::post('merge', 'merge')->name('merge');
    });

    Route::get('changes', [ChangeController::class, 'index'])->name('changes.index');
    Route::post('changes/review', [ChangeController::class, 'reviewMany'])->name('changes.review-many');
    Route::post('changes/{change}/review', [ChangeController::class, 'review'])->name('changes.review');
    Route::post('changes/{change}/revert', [ChangeController::class, 'revert'])->name('changes.revert');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings/media', [SettingsController::class, 'updateMedia'])->name('settings.media.update');

    Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
    Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
});

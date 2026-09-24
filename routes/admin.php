<?php

use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\BusinessStatusController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvitationController;
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

    Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
    Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
});

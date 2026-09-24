<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::controller(InvitationController::class)->prefix('invitations/{token}')->name('invitations.')->group(function () {
    Route::get('/', 'show')->name('show');
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('accept', 'accept')->name('accept');
        Route::post('decline', 'decline')->name('decline');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('workspace', [WorkspaceController::class, 'choose'])->name('workspace.choose');
    Route::put('workspace', [WorkspaceController::class, 'update'])->name('workspace.update');
    Route::get('workspace/none', [WorkspaceController::class, 'none'])->name('workspace.none');
});

require __DIR__.'/app.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';

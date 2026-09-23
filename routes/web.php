<?php

use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('workspace', [WorkspaceController::class, 'choose'])->name('workspace.choose');
    Route::put('workspace', [WorkspaceController::class, 'update'])->name('workspace.update');
    Route::get('workspace/none', [WorkspaceController::class, 'none'])->name('workspace.none');

    Route::middleware('business')->prefix('app')->group(function () {
        Route::inertia('/', 'dashboard')->name('dashboard');
    });
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';

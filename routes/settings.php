<?php

use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\UserDataExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');    Route::get('settings/delete-account', [AccountDeletionController::class, 'show'])->name('account-deletion.show');
    Route::post('settings/delete-account', [AccountDeletionController::class, 'request'])->name('account-deletion.request');
    Route::post('settings/delete-account/cancel', [AccountDeletionController::class, 'cancel'])->name('account-deletion.cancel');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('password.confirm')->group(function () {
        Route::get('settings/data-export', [UserDataExportController::class, 'show'])->name('data-export.show');
        Route::post('settings/data-export', [UserDataExportController::class, 'request'])->middleware('throttle:3,1')->name('data-export.request');
        Route::get('settings/data-export/{token}', [UserDataExportController::class, 'download'])->middleware('throttle:10,1')->name('data-export.download');
    });

    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
    Route::livewire('settings/notifications', 'pages::settings.notifications')->name('notifications.settings');

    Route::livewire('settings/security', 'pages::settings.security')
        /* @chisel-password-confirmation */
        ->middleware([
            'password.confirm',
        ])
        /* @end-chisel-password-confirmation */
        ->name('security.edit');
});

/* @chisel-passkeys */
Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
/* @end-chisel-passkeys */

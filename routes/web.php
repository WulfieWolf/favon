<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PlaceDeletionController;
use App\Http\Controllers\Admin\PlaceMergeController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\SupportContentController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\SystemNotificationController;
use App\Http\Controllers\Admin\SystemToolsController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MailPreviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlaceBrowseController;
use App\Http\Controllers\PlaceProfileController;
use App\Http\Controllers\RolePreviewController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TelegramAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('auth.gateway');
})->name('home');

Route::get('auth/telegram/callback', [TelegramAuthController::class, 'callback'])
    ->middleware('throttle:10,1')
    ->name('telegram.callback');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

if (app()->environment('local')) {
    Route::get('dev/mail/verify-email', [MailPreviewController::class, 'verifyEmail'])
        ->middleware('auth')
        ->name('dev.mail.verify-email');
}

Route::get('impressum', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('nutzungsbedingungen', [LegalController::class, 'terms'])->name('legal.terms');

Route::get('support/report', [SupportTicketController::class, 'create'])->name('support.report');
Route::get('support/datenschutz-recht', [SupportTicketController::class, 'privacyLegal'])->name('support.privacy-legal');
Route::post('support/report', [SupportTicketController::class, 'store'])->middleware('throttle:support-submit')->name('support.store');
Route::get('support/thanks', [SupportTicketController::class, 'thanks'])->name('support.thanks');

Route::middleware(['auth'])->group(function () {
    Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
    Route::get('dashboard', PlaceBrowseController::class)
        ->middleware(['harden-browse', 'limit-filtered-browse'])
        ->name('dashboard');
    Route::get('places/{slug}', PlaceProfileController::class)->name('places.show');

    Route::get('help', [HelpController::class, 'index'])->name('help.index');
    Route::get('help/context', [HelpController::class, 'context'])->name('help.context');
    Route::get('help/{slug}', [HelpController::class, 'show'])->name('help.show');
    Route::get('roadmap', [HelpController::class, 'roadmap'])->name('roadmap');

    Route::middleware('permission:favorites.manage_own')->group(function () {
        Route::get('favorites', fn () => redirect()->route('dashboard', ['favorites' => 1]))->name('favorites.index');
        Route::post('places/{slug}/favorite', [FavoriteController::class, 'store'])->middleware('throttle:engagement-write')->name('favorites.store');
        Route::delete('places/{slug}/favorite', [FavoriteController::class, 'destroy'])->middleware('throttle:engagement-write')->name('favorites.destroy');
    });

    Route::get('notifications', [NotificationController::class, 'index'])->middleware('permission:notifications.view_own')->name('notifications.index');
    Route::get('notifications/{notification}', [NotificationController::class, 'show'])->middleware('permission:notifications.view_own')->name('notifications.show');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->middleware(['permission:notifications.manage_own', 'throttle:engagement-write'])->name('notifications.read-all');

    Route::get('support/my', [SupportTicketController::class, 'myIndex'])->middleware('permission:support.view_own')->name('support.my.index');
    Route::get('support/my/{ticket}', [SupportTicketController::class, 'myShow'])->middleware('permission:support.view_own')->name('support.my.show');
    Route::post('support/my/{ticket}/reply', [SupportTicketController::class, 'reply'])->middleware(['permission:support.reply_own', 'throttle:support-reply'])->name('support.my.reply');

    Route::post('role-preview', [RolePreviewController::class, 'update'])->name('role-preview.update');

    Route::prefix('admin/support')
        ->name('admin.support.')
        ->middleware('permission:support.view_all')
        ->group(function () {
            Route::get('/', [SupportController::class, 'index'])->name('index');
            Route::get('/content', [SupportContentController::class, 'index'])->middleware('permission:support.manage_content')->name('content');
            Route::post('/articles', [SupportContentController::class, 'storeArticle'])->middleware('permission:support.manage_content')->name('articles.store');
            Route::put('/articles/{article}', [SupportContentController::class, 'updateArticle'])->middleware('permission:support.manage_content')->name('articles.update');
            Route::post('/entries', [SupportContentController::class, 'storeEntry'])->middleware('permission:support.manage_content')->name('entries.store');
            Route::put('/entries/{entry}', [SupportContentController::class, 'updateEntry'])->middleware('permission:support.manage_content')->name('entries.update');
            Route::get('/{ticket}', [SupportController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [SupportController::class, 'reply'])->middleware('permission:support.reply')->name('reply');
            Route::post('/{ticket}/note', [SupportController::class, 'internalNote'])->middleware('permission:support.internal_note')->name('note');
            Route::put('/{ticket}', [SupportController::class, 'update'])->middleware('permission:support.change_status')->name('update');
        });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('permission:admin.access')
        ->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('index');
            Route::get('/system', [SystemToolsController::class, 'index'])->name('system.index');
            Route::get('/statistics', [StatisticsController::class, 'index'])->middleware('permission:statistics.view')->name('statistics.index');
            Route::put('/system/debug', [SystemToolsController::class, 'updateDebug'])->name('system.debug');
            Route::put('/system/access', [SystemToolsController::class, 'updateAccess'])->name('system.access');

            Route::get('/users', [AdminController::class, 'users'])->middleware('permission:users.view')->name('users.index');
            Route::get('/users/{user}', [AdminController::class, 'user'])->middleware('permission:users.view_details')->name('users.show');
            Route::put('/users/{user}/account', [AdminController::class, 'updateAccount'])->middleware('permission:users.edit_profile')->name('users.account.update');
            Route::post('/users/{user}/verify-email', [AdminController::class, 'verifyEmail'])->middleware('permission:users.verify_email')->name('users.email.verify');
            Route::post('/users/{user}/suspend', [AdminController::class, 'suspend'])->middleware('permission:users.suspend')->name('users.suspend');
            Route::delete('/users/{user}/suspend', [AdminController::class, 'unsuspend'])->middleware('permission:users.unsuspend')->name('users.unsuspend');
            Route::delete('/users/{user}/account', [AdminController::class, 'deleteAccount'])->middleware('permission:users.delete_account')->name('users.account.delete');
            Route::post('/users/{user}/roles', [AdminController::class, 'assignRole'])->middleware('permission:users.assign_roles')->name('users.roles.assign');
            Route::delete('/users/{user}/roles', [AdminController::class, 'removeRole'])->middleware('permission:users.assign_roles')->name('users.roles.remove');
            Route::put('/users/{user}/permission-overrides', [AdminController::class, 'setOverride'])->middleware('permission:users.override_permissions')->name('users.permissions.override');

            Route::delete('/places/{place}/permanent', [PlaceDeletionController::class, 'destroy'])
                ->middleware('permission:places.delete_permanently')
                ->name('places.delete-permanently');

            Route::middleware('permission:places.merge')->group(function () {
                Route::get('/place-merges', [PlaceMergeController::class, 'index'])->name('place-merges.index');
                Route::post('/place-merges', [PlaceMergeController::class, 'store'])->name('place-merges.store');
                Route::post('/place-merges/{merge}/reverse', [PlaceMergeController::class, 'reverse'])->name('place-merges.reverse');
            });

            Route::middleware('permission:notifications.send_system')->group(function () {
                Route::get('/notifications/create', [SystemNotificationController::class, 'create'])->name('notifications.create');
                Route::post('/notifications', [SystemNotificationController::class, 'store'])->name('notifications.store');
            });

            Route::middleware('permission:audit.view_all')->group(function () {
                Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            });
        });
});

require __DIR__.'/settings.php';

<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ChangeRequestController;
use App\Http\Controllers\Admin\FeatureCatalogController;
use App\Http\Controllers\Admin\PlaceMergeController;
use App\Http\Controllers\Admin\PlaceDeletionController;
use App\Http\Controllers\Admin\ReviewReportController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\SupportContentController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\SystemNotificationController;
use App\Http\Controllers\Admin\SystemToolsController;
use App\Http\Controllers\DevLogController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MailPreviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlaceBrowseController;
use App\Http\Controllers\PlaceContactController;
use App\Http\Controllers\PlaceFeatureController;
use App\Http\Controllers\PlaceInfoSuggestionController;
use App\Http\Controllers\PlaceOpeningHoursController;
use App\Http\Controllers\PlacePriceController;
use App\Http\Controllers\PlaceProfileController;
use App\Http\Controllers\PlaceReviewController;
use App\Http\Controllers\PlaceSuggestionController;
use App\Http\Controllers\RolePreviewController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/', PlaceBrowseController::class)->middleware(['harden-browse', 'throttle:public-read'])->name('home');
Route::get('dashboard', PlaceBrowseController::class)->middleware(['harden-browse', 'throttle:public-read', 'limit-filtered-browse'])->name('dashboard');
Route::get('places/{slug}', PlaceProfileController::class)->middleware('throttle:public-read')->name('places.show');
Route::get('places/{slug}/contact/email', [PlaceContactController::class, 'email'])->middleware('throttle:30,1')->name('places.contact.email');
Route::get('places/{slug}/reviews/feed', [PlaceReviewController::class, 'feed'])->middleware('throttle:public-read')->name('places.reviews.feed');
    ->whereUuid('uuid')
    ->whereIn('variant', ['preview', 'detail'])
    ->name('photos.show');
Route::get('reviews/{review}/history', [PlaceReviewController::class, 'history'])->middleware('throttle:public-read')->name('reviews.history');
Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');
Route::get('devlog', DevLogController::class)->name('devlog');

if (app()->environment('local')) {
    Route::get('dev/mail/verify-email', [MailPreviewController::class, 'verifyEmail'])
        ->middleware('auth')
        ->name('dev.mail.verify-email');
}

Route::get('impressum', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('nutzungsbedingungen', [LegalController::class, 'terms'])->name('legal.terms');

Route::get('help', [HelpController::class, 'index'])->name('help.index');
Route::get('help/context', [HelpController::class, 'context'])->name('help.context');
Route::get('help/{slug}', [HelpController::class, 'show'])->name('help.show');
Route::get('roadmap', [HelpController::class, 'roadmap'])->name('roadmap');

Route::get('support/report', [SupportTicketController::class, 'create'])->name('support.report');
Route::get('support/datenschutz-recht', [SupportTicketController::class, 'privacyLegal'])->name('support.privacy-legal');
Route::post('support/report', [SupportTicketController::class, 'store'])->middleware('throttle:support-submit')->name('support.store');
Route::get('support/thanks', [SupportTicketController::class, 'thanks'])->name('support.thanks');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('places/{slug}/suggest-info', [PlaceInfoSuggestionController::class, 'edit'])->name('places.info-suggest.edit');
    Route::post('places/{slug}/suggest-info', [PlaceInfoSuggestionController::class, 'update'])->middleware('throttle:community-write')->name('places.info-suggest.update');

    Route::get('places/{slug}/opening-hours', [PlaceOpeningHoursController::class, 'edit'])->name('places.opening-hours.edit');
    Route::put('places/{slug}/opening-hours', [PlaceOpeningHoursController::class, 'update'])->middleware('throttle:community-write')->name('places.opening-hours.update');

    Route::get('places/{slug}/prices', [PlacePriceController::class, 'edit'])->name('places.prices.edit');
    Route::put('places/{slug}/prices', [PlacePriceController::class, 'update'])->middleware('throttle:community-write')->name('places.prices.update');

    Route::post('places/{slug}/reviews', [PlaceReviewController::class, 'store'])->middleware(['permission:reviews.create', 'throttle:review-write'])->name('reviews.store');
    Route::delete('places/{slug}/reviews', [PlaceReviewController::class, 'destroy'])->middleware(['permission:reviews.delete_own', 'throttle:community-write'])->name('reviews.destroy');
    Route::delete('reviews/{review}/history/{version}', [PlaceReviewController::class, 'hideVersion'])->middleware(['permission:reviews.delete_own', 'throttle:community-write'])->name('reviews.history.hide');
    Route::post('reviews/{review}/report', [PlaceReviewController::class, 'report'])->middleware(['permission:reports.create', 'throttle:report-create'])->name('reviews.report');
        ->middleware(['permission:photos.upload', 'throttle:photo-upload'])
        ->name('photos.store');
        ->whereUuid('uuid')
        ->whereIn('variant', ['preview', 'detail'])
        ->name('photos.owner');
        ->middleware(['permission:photos.delete_own', 'throttle:community-write'])
        ->name('photos.destroy');
        ->middleware(['permission:reviews.vote_helpful', 'throttle:engagement-write'])
        ->name('photos.helpful.store');
        ->middleware(['permission:reviews.remove_own_helpful_vote', 'throttle:engagement-write'])
        ->name('photos.helpful.destroy');
        ->middleware(['permission:reports.create', 'throttle:report-create'])
        ->name('photos.report');
    Route::put('places/{slug}/features/category/{category}', [PlaceFeatureController::class, 'updateCategory'])->middleware('throttle:community-write')->name('places.features.category.update');
    Route::put('places/{slug}/features/{feature}', [PlaceFeatureController::class, 'update'])->middleware('throttle:community-write')->name('places.features.update');

    Route::middleware('permission:places.suggest')->group(function () {
        Route::get('places/suggest/new', [PlaceSuggestionController::class, 'create'])->name('places.suggest.create');
        Route::get('places/suggest/duplicates', [PlaceSuggestionController::class, 'nearbyDuplicates'])->name('places.suggest.duplicates');
        Route::post('places/suggest', [PlaceSuggestionController::class, 'store'])->middleware('throttle:place-create')->name('places.suggest.store');
        Route::get('places/drafts/{place}/edit', [PlaceSuggestionController::class, 'editDraft'])->name('places.drafts.edit');
        Route::put('places/drafts/{place}', [PlaceSuggestionController::class, 'updateDraft'])->name('places.drafts.update');
        Route::get('places/drafts/{place}/features', [PlaceSuggestionController::class, 'editDraftFeatures'])->name('places.drafts.features.edit');
        Route::put('places/drafts/{place}/features', [PlaceSuggestionController::class, 'updateDraftFeatures'])->middleware('throttle:community-write')->name('places.drafts.features.update');
        Route::get('places/drafts/{place}/review', [PlaceSuggestionController::class, 'reviewDraft'])->name('places.drafts.review');
        Route::post('places/drafts/{place}/submit', [PlaceSuggestionController::class, 'submitDraft'])->middleware('throttle:community-write')->name('places.drafts.submit');
    });

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

    Route::post('role-preview', [RolePreviewController::class, 'update'])->name('role-preview.update');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('permission:admin.access')
        ->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('index');
            Route::get('/system', [SystemToolsController::class, 'index'])->name('system.index');
            Route::get('/statistics', [StatisticsController::class, 'index'])->middleware('permission:statistics.view')->name('statistics.index');
            Route::put('/system/debug', [SystemToolsController::class, 'updateDebug'])->name('system.debug');
            Route::put('/system/access', [SystemToolsController::class, 'updateAccess'])->name('system.access');
            Route::post('/system/data-scores/rebuild', [SystemToolsController::class, 'rebuildDataScores'])->name('system.data-scores.rebuild');
            Route::get('/users', [AdminController::class, 'users'])->middleware('permission:users.view')->name('users.index');
            Route::get('/users/{user}', [AdminController::class, 'user'])->middleware('permission:users.view_details')->name('users.show');
            Route::put('/users/{user}/account', [AdminController::class, 'updateAccount'])->middleware('permission:users.edit_profile')->name('users.account.update');
            Route::post('/users/{user}/verify-email', [AdminController::class, 'verifyEmail'])->middleware('permission:users.verify_email')->name('users.email.verify');
            Route::post('/users/{user}/suspend', [AdminController::class, 'suspend'])->middleware('permission:users.suspend')->name('users.suspend');
            Route::delete('/users/{user}/suspend', [AdminController::class, 'unsuspend'])->middleware('permission:users.unsuspend')->name('users.unsuspend');
            Route::delete('/users/{user}/account', [AdminController::class, 'deleteAccount'])->middleware('permission:users.delete_account')->name('users.account.delete');
            Route::get('/review-reports', [ReviewReportController::class, 'index'])->middleware('permission:reports.view_all')->name('review-reports.index');
            Route::post('/review-reports/{report}/remove', [ReviewReportController::class, 'remove'])->middleware('permission:reports.handle')->name('review-reports.remove');
            Route::post('/review-reports/{report}/dismiss', [ReviewReportController::class, 'dismiss'])->middleware('permission:reports.handle')->name('review-reports.dismiss');
            Route::post('/users/{user}/roles', [AdminController::class, 'assignRole'])->middleware('permission:users.assign_roles')->name('users.roles.assign');
            Route::delete('/users/{user}/roles', [AdminController::class, 'removeRole'])->middleware('permission:users.assign_roles')->name('users.roles.remove');
            Route::put('/users/{user}/permission-overrides', [AdminController::class, 'setOverride'])->middleware('permission:users.override_permissions')->name('users.permissions.override');

            Route::middleware('permission:features.manage_catalog')->group(function () {
                Route::get('/features', [FeatureCatalogController::class, 'index'])->name('features.index');
                Route::post('/features', [FeatureCatalogController::class, 'storeFeature'])->name('features.store');
                Route::put('/features/{feature}', [FeatureCatalogController::class, 'updateFeature'])->name('features.update');
                Route::post('/feature-categories', [FeatureCatalogController::class, 'storeCategory'])->name('feature-categories.store');
                Route::put('/feature-categories/{category}', [FeatureCatalogController::class, 'updateCategory'])->name('feature-categories.update');
            });

            Route::delete('/places/{place}/permanent', [PlaceDeletionController::class, 'destroy'])
                ->middleware('permission:places.delete_permanently')
                ->name('places.delete-permanently');

            Route::middleware('permission:places.merge')->group(function () {
                Route::get('/place-merges', [PlaceMergeController::class, 'index'])->name('place-merges.index');
                Route::post('/place-merges', [PlaceMergeController::class, 'store'])->name('place-merges.store');
                Route::post('/place-merges/{merge}/reverse', [PlaceMergeController::class, 'reverse'])->name('place-merges.reverse');
            });

            Route::middleware('permission:places.approve_changes')->group(function () {
                Route::get('/change-requests', [ChangeRequestController::class, 'index'])->name('change-requests.index');
                Route::get('/change-requests/{changeRequest}', [ChangeRequestController::class, 'show'])->name('change-requests.show');
                Route::post('/change-requests/{changeRequest}/approve', [ChangeRequestController::class, 'approve'])->name('change-requests.approve');
                Route::post('/change-requests/{changeRequest}/reject', [ChangeRequestController::class, 'reject'])->name('change-requests.reject');
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

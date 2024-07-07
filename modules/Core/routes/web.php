<?php
 

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Middleware\CheckResponseForModifications;
use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Controllers\ApplicationController;
use Modules\Core\App\Http\Controllers\FilePermissionsError;
use Modules\Core\App\Http\Controllers\FinalizeUpdateController;
use Modules\Core\App\Http\Controllers\MediaViewController;
use Modules\Core\App\Http\Controllers\MigrateController;
use Modules\Core\App\Http\Controllers\OAuthController;
use Modules\Core\App\Http\Controllers\PrivacyPolicy;
use Modules\Core\App\Http\Controllers\ScriptController;
use Modules\Core\App\Http\Controllers\StyleController;
use Modules\Core\App\Http\Controllers\SynchronizationGoogleWebhookController;
use Modules\Core\App\Http\Controllers\UpdateDownloadController;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenMigrationNeeded;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenUpdateNotFinished;

Route::withoutMiddleware(CheckResponseForModifications::class)->group(function () {
    Route::get('/scripts/{script}', [ScriptController::class, 'show']);
    Route::get('/styles/{style}', [StyleController::class, 'show']);
});

Route::withoutMiddleware(VerifyCsrfToken::class)->group(function () {
    Route::post('/webhook/google', [SynchronizationGoogleWebhookController::class, 'handle']);
});

Route::withoutMiddleware(PreventRequestsWhenUpdateNotFinished::class)->group(function () {
    Route::get('/update/finalize', [FinalizeUpdateController::class, 'show']);
    Route::post('/update/finalize', [FinalizeUpdateController::class, 'finalize']);
});

Route::withoutMiddleware([
    PreventRequestsWhenMigrationNeeded::class,
    PreventRequestsWhenUpdateNotFinished::class,
])->group(function () {
    Route::get('/migrate', [MigrateController::class, 'show']);
    Route::post('/migrate', [MigrateController::class, 'migrate']);

    Route::get('privacy-policy', PrivacyPolicy::class);

    Route::get('/media/{token}', [MediaViewController::class, 'show']);
    Route::get('/media/{token}/download', [MediaViewController::class, 'download']);
    Route::get('/media/{token}/preview', [MediaViewController::class, 'preview']);
});

Route::middleware('auth')->group(function () {
    Route::get('/{providerName}/connect', [OAuthController::class, 'connect'])->where('providerName', 'microsoft|google');
    Route::get('/{providerName}/callback', [OAuthController::class, 'callback'])->where('providerName', 'microsoft|google');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/errors/permissions', FilePermissionsError::class);

    Route::get('/patches/{token}/{purchase_key?}', [UpdateDownloadController::class, 'downloadPatch']);
});

Route::middleware(['auth'])->group(function () {
    Route::fallback(ApplicationController::class);
});

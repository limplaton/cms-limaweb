<?php
 

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\Activities\App\Http\Controllers\OAuthCalendarController;
use Modules\Activities\App\Http\Controllers\OutlookCalendarWebhookController;

Route::withoutMiddleware(VerifyCsrfToken::class)->group(function () {
    Route::post('/webhook/outlook-calendar', [OutlookCalendarWebhookController::class, 'handle']);
});

Route::middleware('auth')->group(function () {
    Route::get('/calendar/sync/{provider}/connect', [OAuthCalendarController::class, 'connect']);
});

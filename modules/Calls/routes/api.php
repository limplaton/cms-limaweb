<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Calls\App\Http\Controllers\Api\TwilioAppController;
use Modules\Calls\App\Http\Controllers\Api\TwilioController;
use Modules\Calls\App\Http\Controllers\Api\VoIPController;

Route::post('/voip/events', [VoIPController::class, 'events'])->name('voip.events');
Route::post('/voip/call', [VoIPController::class, 'newCall'])->name('voip.call');

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('permission:use voip')->group(function () {
        Route::get('/voip/token', [VoIPController::class, 'newToken']);
    });

    Route::middleware('admin')->group(function () {
        // Twilio integration routes
        Route::prefix('twilio')->group(function () {
            Route::delete('/', [TwilioController::class, 'destroy']);
            Route::get('numbers', [TwilioController::class, 'index']);

            Route::get('app/{id}', [TwilioAppController::class, 'show']);
            Route::post('app', [TwilioAppController::class, 'create']);
            Route::put('app/{id}', [TwilioAppController::class, 'update']);
            Route::delete('app/{sid}', [TwilioAppController::class, 'destroy']);
        });
    });
});

<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Documents\App\Http\Controllers\Api\DocumentAcceptController;
use Modules\Documents\App\Http\Controllers\Api\DocumentStateController;

Route::post('/d/{uuid}/accept', [DocumentAcceptController::class, 'accept']);
Route::post('/d/{uuid}/sign', [DocumentAcceptController::class, 'sign']);
Route::post('/d/{uuid}/validate', [DocumentAcceptController::class, 'validateEmailAddress']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/documents/{document}/lost', [DocumentStateController::class, 'lost']);
    Route::post('/documents/{document}/accept', [DocumentStateController::class, 'accept']);
    Route::post('/documents/{document}/draft', [DocumentStateController::class, 'draft']);
});

<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Comments\App\Http\Controllers\Api\CommentController;

Route::middleware('auth:sanctum')->group(function () {
    // Comments management
    Route::get('{resource}/{resourceId}/comments', [CommentController::class, 'index']);
    Route::post('{resource}/{resourceId}/comments', [CommentController::class, 'store']);
    Route::get('/comments/{comment}', [CommentController::class, 'show']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});

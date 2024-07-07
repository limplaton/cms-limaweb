<?php
 

use Illuminate\Support\Facades\Route;
use Modules\WebForms\App\Http\Controllers\Api\WebFormController;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::apiResource('/forms', WebFormController::class);
    });
});

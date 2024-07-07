<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Billable\App\Http\Controllers\Api\ActiveProductController;
use Modules\Billable\App\Http\Controllers\Api\BillableController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/{resource}/{resourceId}/billable', [BillableController::class, 'show']);
    Route::post('/{resource}/{resourceId}/billable', [BillableController::class, 'save']);

    Route::get('/{resource}/active', [ActiveProductController::class, 'handle']);
});

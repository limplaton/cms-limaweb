<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Deals\App\Http\Controllers\Api\DealBoardController;
use Modules\Deals\App\Http\Controllers\Api\DealStatusController;
use Modules\Deals\App\Http\Controllers\Api\PipelineStageController;
use Modules\Deals\App\Http\Controllers\Api\UpdatePipelineDisplayOrder;

Route::middleware('auth:sanctum')->group(function () {
    /**
     * @deprecated Use regular deal update with "status" attribute.
     */
    Route::put('/deals/{deal}/status/{status}', [DealStatusController::class, 'handle']);

    Route::prefix('deals/board')->group(function () {
        Route::get('{pipeline}', [DealBoardController::class, 'board']);
        Route::post('{pipeline}', [DealBoardController::class, 'update']);
        Route::get('{pipeline}/summary/{stageId?}', [DealBoardController::class, 'summary']);
        Route::get('{pipeline}/{stageId}', [DealBoardController::class, 'load']);
    });

    Route::get('/pipelines/{pipeline}/stages', [PipelineStageController::class, 'index']);
    Route::post('/pipelines/order', UpdatePipelineDisplayOrder::class);
});

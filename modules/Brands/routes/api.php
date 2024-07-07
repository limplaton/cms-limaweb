<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Brands\App\Http\Controllers\Api\BrandController;
use Modules\Brands\App\Http\Controllers\Api\BrandLogoController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/brands/{brand}/logo/{type}', [BrandLogoController::class, 'store'])->where('type', 'mail|view');
    Route::delete('/brands/{brand}/logo/{type}', [BrandLogoController::class, 'delete'])->where('type', 'mail|view');

    Route::apiResource('brands', BrandController::class);
});

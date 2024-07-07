<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenMigrationNeeded;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenUpdateNotFinished;
use Modules\Documents\App\Http\Controllers\DocumentController;

Route::withoutMiddleware([
    PreventRequestsWhenMigrationNeeded::class,
    PreventRequestsWhenUpdateNotFinished::class,
])->group(function () {
    Route::get('/d/{uuid}', [DocumentController::class, 'show'])->name('document.public');
    Route::get('/d/{uuid}/pdf', [DocumentController::class, 'pdf'])->name('document.pdf');
});

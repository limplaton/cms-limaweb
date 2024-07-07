<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenMigrationNeeded;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenUpdateNotFinished;
use Modules\WebForms\App\Http\Controllers\WebFormController;

Route::withoutMiddleware([
    PreventRequestsWhenMigrationNeeded::class,
    PreventRequestsWhenUpdateNotFinished::class,
])->group(function () {
    Route::get('/forms/f/{uuid}', [WebFormController::class, 'show'])->name('webform.view');
    Route::post('/forms/f/{uuid}', [WebFormController::class, 'store'])->name('webform.process');
});

<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenMigrationNeeded;
use Modules\Core\App\Http\Middleware\PreventRequestsWhenUpdateNotFinished;
use Modules\Users\App\Http\Controllers\UserInvitationAcceptController;

Route::withoutMiddleware([
    PreventRequestsWhenMigrationNeeded::class,
    PreventRequestsWhenUpdateNotFinished::class,
])->group(function () {
    Route::get('/invitation/{token}', [UserInvitationAcceptController::class, 'show'])->name('invitation.show');
    Route::post('/invitation/{token}', [UserInvitationAcceptController::class, 'accept']);
});

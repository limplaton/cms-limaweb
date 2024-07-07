<?php

use App\Http\Controllers\RequirementsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes(['register' => false, 'verify' => false, 'confirm' => false]);

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/requirements', [RequirementsController::class, 'show']);
    Route::post('/requirements', [RequirementsController::class, 'confirm']);
});

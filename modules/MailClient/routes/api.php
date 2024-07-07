<?php
 

use Illuminate\Support\Facades\Route;
use Modules\MailClient\App\Http\Controllers\Api\EmailAccountConnectionTestController;
use Modules\MailClient\App\Http\Controllers\Api\EmailAccountController;
use Modules\MailClient\App\Http\Controllers\Api\EmailAccountMessagesController;
use Modules\MailClient\App\Http\Controllers\Api\EmailAccountMessageTagController;
use Modules\MailClient\App\Http\Controllers\Api\EmailAccountPrimaryStateController;
use Modules\MailClient\App\Http\Controllers\Api\EmailAccountSync;
use Modules\MailClient\App\Http\Controllers\Api\EmailAccountSyncStateController;
use Modules\MailClient\App\Http\Controllers\Api\PersonalEmailAccountController;
use Modules\MailClient\App\Http\Controllers\Api\PredefinedMailTemplateController;
use Modules\MailClient\App\Http\Controllers\Api\ScheduledEmailController;
use Modules\MailClient\App\Http\Controllers\Api\ScheduledEmailCountController;
use Modules\MailClient\App\Http\Controllers\Api\ScheduledEmailSendController;
use Modules\MailClient\App\Http\Controllers\Api\SharedEmailAccountController;

Route::middleware('auth:sanctum')->group(function () {
    // Email accounts routes
    Route::prefix('mail/accounts')->group(function () {
        // Email accounts management
        Route::get('{account}/sync', EmailAccountSync::class);
        Route::get('unread', [EmailAccountController::class, 'unread']);

        // The GET route for all shared accounts
        Route::get('shared', SharedEmailAccountController::class)->middleware('permission:access shared inbox');

        // The GET route for all logged in user personal mail accounts
        Route::get('personal', PersonalEmailAccountController::class);

        // Test connection route
        Route::post('connection', [EmailAccountConnectionTestController::class, 'handle']);

        Route::put('{account}/primary', [EmailAccountPrimaryStateController::class, 'update']);
        Route::delete('primary', [EmailAccountPrimaryStateController::class, 'destroy']);
        Route::post('{account}/sync/enable', [EmailAccountSyncStateController::class, 'enable']);
        Route::post('{account}/sync/disable', [EmailAccountSyncStateController::class, 'disable']);
    });

    Route::apiResource('/mail/accounts', EmailAccountController::class);

    Route::prefix('emails')->group(function () {
        Route::get('scheduled', [ScheduledEmailController::class, 'index']);
        Route::get('scheduled/count', ScheduledEmailCountController::class);
        Route::delete('scheduled/{id}', [ScheduledEmailController::class, 'destroy']);
        Route::post('scheduled/{id}/send', ScheduledEmailSendController::class);

        Route::post('{message}/read', [EmailAccountMessagesController::class, 'read']);
        Route::post('{message}/unread', [EmailAccountMessagesController::class, 'unread']);
        Route::delete('{message}', [EmailAccountMessagesController::class, 'destroy']);

        // reply method is used to check in MessageRequest
        Route::post('{message}/reply', [EmailAccountMessagesController::class, 'reply']);
        Route::post('{message}/forward', [EmailAccountMessagesController::class, 'forward']);
        Route::post('{message}/tags', EmailAccountMessageTagController::class);
    });

    Route::prefix('inbox')->group(function () {
        Route::get('emails/folders/{folder_id}/{message}', [EmailAccountMessagesController::class, 'show']);
        Route::post('emails/{account_id}', [EmailAccountMessagesController::class, 'create']);
        Route::get('emails/{account_id}/{folder_id}', [EmailAccountMessagesController::class, 'index']);
    });

    // Mail templates management
    Route::apiResource('mails/templates', PredefinedMailTemplateController::class);
});

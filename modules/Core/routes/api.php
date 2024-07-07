<?php
 

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Http\Controllers\Api\CalendarController;
use Modules\Core\App\Http\Controllers\Api\CardController;
use Modules\Core\App\Http\Controllers\Api\CustomFieldController;
use Modules\Core\App\Http\Controllers\Api\DashboardController;
use Modules\Core\App\Http\Controllers\Api\ExecuteTool;
use Modules\Core\App\Http\Controllers\Api\FieldSettingsController;
use Modules\Core\App\Http\Controllers\Api\FilterController;
use Modules\Core\App\Http\Controllers\Api\LogoController;
use Modules\Core\App\Http\Controllers\Api\MailableTemplateController;
use Modules\Core\App\Http\Controllers\Api\MenuController;
use Modules\Core\App\Http\Controllers\Api\OAuthAccountController;
use Modules\Core\App\Http\Controllers\Api\PatchController;
use Modules\Core\App\Http\Controllers\Api\PendingMediaController;
use Modules\Core\App\Http\Controllers\Api\PermissionController;
use Modules\Core\App\Http\Controllers\Api\Resource\ActionController;
use Modules\Core\App\Http\Controllers\Api\Resource\AssociationsController;
use Modules\Core\App\Http\Controllers\Api\Resource\AssociationsSyncController;
use Modules\Core\App\Http\Controllers\Api\Resource\CloneController;
use Modules\Core\App\Http\Controllers\Api\Resource\EmailSearchController;
use Modules\Core\App\Http\Controllers\Api\Resource\EmptyTrash;
use Modules\Core\App\Http\Controllers\Api\Resource\ExportController;
use Modules\Core\App\Http\Controllers\Api\Resource\FieldController;
use Modules\Core\App\Http\Controllers\Api\Resource\FilterRulesController;
use Modules\Core\App\Http\Controllers\Api\Resource\GlobalSearchController;
use Modules\Core\App\Http\Controllers\Api\Resource\ImportController;
use Modules\Core\App\Http\Controllers\Api\Resource\ImportSkipFileController;
use Modules\Core\App\Http\Controllers\Api\Resource\MediaController;
use Modules\Core\App\Http\Controllers\Api\Resource\PlaceholdersController;
use Modules\Core\App\Http\Controllers\Api\Resource\ResourceController;
use Modules\Core\App\Http\Controllers\Api\Resource\SearchController;
use Modules\Core\App\Http\Controllers\Api\Resource\TableController;
use Modules\Core\App\Http\Controllers\Api\Resource\TimelineController;
use Modules\Core\App\Http\Controllers\Api\Resource\TrashedController;
use Modules\Core\App\Http\Controllers\Api\RetrieveCountries;
use Modules\Core\App\Http\Controllers\Api\RetrieveCurrencies;
use Modules\Core\App\Http\Controllers\Api\RetrieveTimezones;
use Modules\Core\App\Http\Controllers\Api\RoleController;
use Modules\Core\App\Http\Controllers\Api\SettingsController;
use Modules\Core\App\Http\Controllers\Api\SystemController;
use Modules\Core\App\Http\Controllers\Api\TagController;
use Modules\Core\App\Http\Controllers\Api\TimelinePinController;
use Modules\Core\App\Http\Controllers\Api\UpdateController;
use Modules\Core\App\Http\Controllers\Api\UpdateTagDisplayOrder;
use Modules\Core\App\Http\Controllers\Api\WorkflowController;
use Modules\Core\App\Http\Controllers\Api\WorkflowTriggers;
use Modules\Core\App\Http\Controllers\Api\ZapierHookController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/zapier/hooks/{resourceName}/{action}', [ZapierHookController::class, 'store']);
    Route::delete('/zapier/hooks/{hookId}', [ZapierHookController::class, 'destroy']);

    // Calendar routes
    Route::get('/calendar', [CalendarController::class, 'index']);

    // OAuth accounts controller
    Route::apiResource('/oauth/accounts', OAuthAccountController::class, ['as' => 'oauth'])
        ->except(['store', 'update']);

    // Menu routes
    Route::get('menu/metrics', [MenuController::class, 'metrics']);

    // Available timezones route
    Route::get('/timezones', RetrieveTimezones::class);

    // Available countries route
    Route::get('/countries', RetrieveCountries::class);

    // App available currencies
    Route::get('currencies', RetrieveCurrencies::class);

    Route::middleware('admin')->group(function () {
        Route::post('/tools/{tool}', ExecuteTool::class);

        Route::post('/tags/order', UpdateTagDisplayOrder::class);
        Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
        Route::post('/tags/{type?}', [TagController::class, 'store']);
        Route::put('/tags/{tag}', [TagController::class, 'update']);

        Route::get('/system/logs', [SystemController::class, 'logs']);
        Route::get('/system/info', [SystemController::class, 'info']);
        Route::post('/system/info', [SystemController::class, 'downloadInfo']);

        // General Settings
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::post('/settings', [SettingsController::class, 'save']);

        // Application update management
        Route::get('/patches', [PatchController::class, 'index']);
        Route::post('/patches/{token}/{purchase_key?}', [PatchController::class, 'apply']);
        Route::get('/update', [UpdateController::class, 'index']);
        Route::post('/update/{purchase_key?}', [UpdateController::class, 'update']);

        // Custom fields routes
        Route::apiResource('/custom-fields', CustomFieldController::class);

        // Settings intended fields
        Route::prefix('fields/settings')->group(function () {
            Route::post('{group}/{view}', [FieldSettingsController::class, 'update']);
            Route::get('bulk/{view}', [FieldSettingsController::class, 'bulkSettings']);
            Route::get('{group}/{view}', [FieldSettingsController::class, 'settings']);
            Route::delete('{group}/{view}/reset', [FieldSettingsController::class, 'destroy']);
        });

        // Workflows
        Route::get('/workflows/triggers', WorkflowTriggers::class);
        Route::apiResource('workflows', WorkflowController::class);

        // Mailable templates
        Route::get('/mailable-templates', [MailableTemplateController::class, 'index']);
        Route::get('/mailable-templates/{locale}/locale', [MailableTemplateController::class, 'forLocale']);
        Route::get('/mailable-templates/{template}', [MailableTemplateController::class, 'show']);
        Route::put('/mailable-templates/{template}', [MailableTemplateController::class, 'update']);

        // Settings roles and permissions
        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::apiResource('roles', RoleController::class);

        // Logo
        Route::post('/logo/{type}', [LogoController::class, 'store'])->where('type', 'dark|light');
        Route::delete('/logo/{type}', [LogoController::class, 'destroy'])->where('type', 'dark|light');
    });

    // Filters management
    Route::prefix('filters')->group(function () {
        Route::put('{filter}/{view}/default', [FilterController::class, 'markAsDefault']);
        Route::delete('{filter}/{view}/default', [FilterController::class, 'unmarkAsDefault']);
        Route::get('{identifier}', [FilterController::class, 'index']);
        Route::post('/', [FilterController::class, 'store']);
        Route::put('{filter}', [FilterController::class, 'update']);
        Route::delete('{filter}', [FilterController::class, 'destroy']);
    });

    // Media routes
    Route::post('/media/pending/{draftId}', [PendingMediaController::class, 'store']);
    Route::delete('/media/pending/{pendingMediaId}', [PendingMediaController::class, 'destroy']);

    // Cards controller
    Route::get('/cards', [CardController::class, 'forDashboards']);
    Route::get('/cards/{card}', [CardController::class, 'show'])->name('cards.show');

    // Used by Zapier
    Route::get('/fields/{group}/create', function ($group) {
        return response()->json(
            Fields::get($group, Fields::CREATE_VIEW)->filterForCreation()->visibleOnCreate()
        );
    });

    Route::get('/fields/{group}/update', function ($group) {
        return response()->json(
            Fields::get($group, Fields::UPDATE_VIEW)->filterForUpdate()->visibleOnUpdate()
        );
    });

    // Dashboard controller
    Route::apiResource('dashboards', DashboardController::class);

    // Cards controller
    Route::get('/{resource}/cards/', [CardController::class, 'index']);

    // Timeline pins management
    Route::post('/timeline/pin', [TimelinePinController::class, 'store']);
    Route::post('/timeline/unpin', [TimelinePinController::class, 'destroy']);

    // Resource placeholders management
    Route::get('/placeholders', [PlaceholdersController::class, 'index']);
    Route::post('/placeholders/input-fields', [PlaceholdersController::class, 'parseViaInputFields']);
    Route::post('/placeholders/interpolation', [PlaceholdersController::class, 'parseViaInterpolation']);

    // Filters management
    Route::get('/{resource}/rules', [FilterRulesController::class, 'index']);

    // Resource import handling
    Route::get('/{resource}/import', [ImportController::class, 'index']);
    Route::post('/{resource}/import/upload', [ImportController::class, 'upload']);
    Route::post('/{resource}/import/{id}', [ImportController::class, 'handle']);
    Route::delete('/{resource}/import/{id}', [ImportController::class, 'destroy']);
    Route::get('/{resource}/import/sample', [ImportController::class, 'sample']);
    Route::delete('/{resource}/import/{id}/revert', [ImportController::class, 'revert']);
    Route::get('/{resource}/import/{id}/skip-file', [ImportSkipFileController::class, 'download']);
    Route::post('/{resource}/import/{id}/skip-file', [ImportSkipFileController::class, 'upload']);

    Route::post('/{resource}/export', [ExportController::class, 'handle']);

    // Searches
    Route::get('/search', [GlobalSearchController::class, 'handle']);
    Route::get('/search/email-address', [EmailSearchController::class, 'handle']);
    Route::get('/{resource}/search', [SearchController::class, 'handle']);

    // Resource associations routes
    Route::put('associations/{resource}/{resourceId}', [AssociationsSyncController::class, 'attach']);
    Route::post('associations/{resource}/{resourceId}', [AssociationsSyncController::class, 'sync']);
    Route::delete('associations/{resource}/{resourceId}', [AssociationsSyncController::class, 'detach']);
    Route::get('associations/{resource}/{resourceId}', [AssociationsController::class, 'index']);

    // Resource media routes
    Route::post('{resource}/{resourceId}/media', [MediaController::class, 'store']);
    Route::delete('{resource}/{resourceId}/media/{media}', [MediaController::class, 'destroy']);

    // Resource trash
    Route::get('/trashed/{resource}/search', [TrashedController::class, 'search']);
    Route::post('/trashed/{resource}/{resourceId}', [TrashedController::class, 'restore']);
    Route::get('/trashed/{resource}', [TrashedController::class, 'index']);
    Route::get('/trashed/{resource}/{resourceId}', [TrashedController::class, 'show']);
    Route::delete('/trashed/{resource}', EmptyTrash::class);
    Route::delete('/trashed/{resource}/{resourceId}', [TrashedController::class, 'destroy']);

    // Resource management
    Route::get('/{resource}/table', [TableController::class, 'index']);
    Route::get('/{resource}/table/settings', [TableController::class, 'settings']);
    Route::post('/{resource}/table/settings', [TableController::class, 'customize']);

    Route::post('/{resource}/actions/{action}/run', [ActionController::class, 'handle']);

    Route::get('/{resource}/{resourceId}/update-fields', [FieldController::class, 'update']);
    Route::get('/{resource}/{resourceId}/detail-fields', [FieldController::class, 'detail']);
    Route::get('/{resource}/{resourceId}/timeline', [TimelineController::class, 'index']);
    Route::post('/{resource}/{resourceId}/clone', [CloneController::class, 'handle']);
    Route::get('/{resource}/{resourceId}/{associatedResource}', [AssociationsController::class, 'show']);
    Route::get('/{resource}/index-fields', [FieldController::class, 'index']);
    Route::get('/{resource}/create-fields', [FieldController::class, 'create']);
    Route::get('/{resource}/export-fields', [FieldController::class, 'export']);

    Route::get('/{resource}', [ResourceController::class, 'index']);
    Route::get('/{resource}/{resourceId}', [ResourceController::class, 'show']);
    Route::post('/{resource}', [ResourceController::class, 'store']);
    Route::put('/{resource}/{resourceId}', [ResourceController::class, 'update']);
    Route::delete('/{resource}/{resourceId}', [ResourceController::class, 'destroy']);
});

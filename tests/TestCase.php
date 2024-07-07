<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Collection;
use Modules\Core\App\Application;
use Modules\Core\App\Facades\Notifications;
use Modules\Core\App\Fields\CustomFieldFileCache;
use Modules\Core\App\Fields\FieldsManager;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Support\GateHelper;
use Modules\Core\App\Support\ModelFinder;
use Modules\Core\App\Workflow\Action as WorkflowAction;
use Modules\Core\App\Workflow\Workflows;
use Modules\Users\App\Support\TeamCache;
use Tests\Fixtures\CalendarResource;
use Tests\Fixtures\EventResource;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication,
        CreatesUser,
        RefreshDatabase;

    /**
     * Setup the tests.
     */
    protected function setUp(): void
    {
        $_SERVER['_VERSION'] = Application::VERSION;

        Application::$resources = new Collection;
        Application::$provideToScript = [];

        Workflows::$triggers = [];
        Workflows::$eventOnlyListeners = [];
        Workflows::$processed = [];

        parent::setUp();

        $this->withoutMiddleware([
            \Modules\Core\App\Http\Middleware\PreventRequestsWhenUpdateNotFinished::class,
            \Modules\Core\App\Http\Middleware\PreventRequestsWhenMigrationNeeded::class,
        ]);

        $this->registerTestResources();
    }

    /**
     * Register the tests resources.
     */
    protected function registerTestResources(): void
    {
        Application::resources([
            EventResource::class,
            CalendarResource::class,
        ]);
    }

    /**
     * Flush any cache and clear registered data.
     */
    protected function flushCacheAndClearData(): void
    {
        $this->tearDownChangelog();

        Resource::clearRegisteredResources();
        FieldsManager::flushCache();
        Notifications::enable();
        TeamCache::flush();
        CustomFieldFileCache::flush();
        WorkflowAction::disableExecutions(false);
        GateHelper::flushCache();
        \Spatie\Once\Cache::getInstance()->flush();
    }

    /**
     * Teardown changelog data.
     */
    protected function tearDownChangelog(): void
    {
        foreach (ModelFinder::find() as $model) {
            if (method_exists($model, 'logsModelChanges') && $model::logsModelChanges()) {
                $model::$afterSyncCustomFieldOptions[$model] = [];
                $model::$beforeSyncCustomFieldOptions[$model] = [];

                $model::$changesPipes = [];
            }
        }
    }

    /**
     * Tear down the tests.
     */
    protected function tearDown(): void
    {
        $this->flushCacheAndClearData();

        parent::tearDown();
    }
}

<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->afterApplicationCreated(function () {
            $this->configureFactoryResolver();
        });

        return $app;
    }

    /**
     * Configure tests factory resolver.
     */
    protected function configureFactoryResolver(): void
    {
        Factory::guessFactoryNamesUsing(function ($modelName) {
            $appNamespace = 'App\\';
            $testNameSpace = __NAMESPACE__.'\\';
            $laravelFactoriesNamespace = 'Database\\Factories\\';
            $testsFactoriesNamespace = 'Tests\\Factories\\';

            if (Str::startsWith($modelName, $testNameSpace.'Fixtures\\')) {
                return $testsFactoriesNamespace.Str::after($modelName, $testNameSpace.'Fixtures\\').'Factory';
            }

            if (Str::startsWith($modelName, $appNamespace.'Models\\')) {
                $modelName = Str::after($modelName, $appNamespace.'Models\\');
            } else {
                $modelName = Str::after($modelName, $appNamespace);
            }

            return $laravelFactoriesNamespace.$modelName.'Factory';
        });
    }
}

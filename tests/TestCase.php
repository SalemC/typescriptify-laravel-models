<?php

namespace SalemC\TypeScriptifyLaravelModels\Tests;

use SalemC\TypeScriptifyLaravelModels\Providers\TypeScriptifyModelsServiceProvider;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations() {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app) {
        return [TypeScriptifyModelsServiceProvider::class];
    }
}

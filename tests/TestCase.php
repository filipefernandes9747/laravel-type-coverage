<?php

namespace Filipefernandes\LaravelTypeCoverage\Tests;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Filipefernandes\LaravelTypeCoverage\LaravelTypeCoverageServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Optionally, set config values here
        $app['config']->set('app.debug', true);
    }

    // Ensure the "app" directory and a dummy file are created
    protected function setUp(): void
    {
        parent::setUp();
    }

    // Clean up the "app" directory after the test
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}

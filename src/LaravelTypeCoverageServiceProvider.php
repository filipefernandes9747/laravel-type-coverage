<?php


namespace Filipefernandes\LaravelTypeCoverage;

use Illuminate\Support\ServiceProvider;
use Filipefernandes\LaravelTypeCoverage\Console\CoverageCommand;

class LaravelTypeCoverageServiceProvider extends ServiceProvider
{

    /**
     * The service provider bootstraps the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/type-coverage.php', 'type-coverage');
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CoverageCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/type-coverage.php' => config_path('type-coverage.php'),
        ], 'config');
    }
}

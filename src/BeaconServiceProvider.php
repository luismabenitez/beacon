<?php

namespace Luismabenitez\Beacon;

use Illuminate\Support\ServiceProvider;
use Luismabenitez\Beacon\Contracts\ErrorReporterInterface;
use Luismabenitez\Beacon\Reporters\HttpErrorReporter;

class BeaconServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/beacon.php',
            'beacon'
        );

        $this->app->singleton(ErrorReporterInterface::class, function ($app) {
            return new HttpErrorReporter(
                $app['config']->get('beacon', [])
            );
        });

        // Alias for easier resolution
        $this->app->alias(ErrorReporterInterface::class, 'beacon');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/beacon.php' => config_path('beacon.php'),
            ], 'beacon-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            ErrorReporterInterface::class,
            'beacon',
        ];
    }
}

<?php

namespace Luismabenitez\Beacon\Tests;

use Luismabenitez\Beacon\BeaconServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BeaconServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Beacon' => \Luismabenitez\Beacon\Facades\Beacon::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('beacon.enabled', true);
        $app['config']->set('beacon.project_key', 'test-project-key');
        $app['config']->set('beacon.endpoint', 'https://test.local/api/error-monitor/report');
    }
}

<?php

namespace Luismabenitez\Beacon\Tests;

use Luismabenitez\Beacon\Support\ErrorContextBuilder;
use RuntimeException;

class ErrorContextBuilderTest extends TestCase
{
    public function test_builds_exception_data(): void
    {
        $builder = new ErrorContextBuilder($this->app['config']->get('beacon'));

        $exception = new RuntimeException('Test error message', 500);
        $payload = $builder->build($exception);

        $this->assertEquals('RuntimeException', $payload['exception']['class']);
        $this->assertEquals('Test error message', $payload['exception']['message']);
        $this->assertEquals(500, $payload['exception']['code']);
        $this->assertArrayHasKey('stacktrace', $payload['exception']);
    }

    public function test_includes_project_key(): void
    {
        $builder = new ErrorContextBuilder($this->app['config']->get('beacon'));

        $payload = $builder->build(new RuntimeException('Test'));

        $this->assertEquals('test-project-key', $payload['project_key']);
    }

    public function test_includes_server_data(): void
    {
        $builder = new ErrorContextBuilder($this->app['config']->get('beacon'));

        $payload = $builder->build(new RuntimeException('Test'));

        $this->assertArrayHasKey('server', $payload);
        $this->assertEquals(PHP_VERSION, $payload['server']['php_version']);
        $this->assertArrayHasKey('laravel_version', $payload['server']);
    }

    public function test_includes_extra_context(): void
    {
        $builder = new ErrorContextBuilder($this->app['config']->get('beacon'));

        $payload = $builder->build(new RuntimeException('Test'), [
            'order_id' => 123,
            'tags' => ['checkout', 'payment'],
        ]);

        $this->assertEquals(123, $payload['context']['extra']['order_id']);
        $this->assertEquals(['checkout', 'payment'], $payload['context']['tags']);
    }

    public function test_includes_timestamp(): void
    {
        $builder = new ErrorContextBuilder($this->app['config']->get('beacon'));

        $payload = $builder->build(new RuntimeException('Test'));

        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $payload['timestamp']);
    }
}

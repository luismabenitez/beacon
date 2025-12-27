<?php

namespace Luismabenitez\Beacon\Tests;

use Illuminate\Validation\ValidationException;
use Luismabenitez\Beacon\Contracts\ErrorReporterInterface;
use Luismabenitez\Beacon\Reporters\HttpErrorReporter;
use RuntimeException;

class HttpErrorReporterTest extends TestCase
{
    public function test_reporter_is_bound_in_container(): void
    {
        $reporter = $this->app->make(ErrorReporterInterface::class);

        $this->assertInstanceOf(HttpErrorReporter::class, $reporter);
    }

    public function test_reporter_is_enabled_when_configured(): void
    {
        $reporter = $this->app->make(ErrorReporterInterface::class);

        $this->assertTrue($reporter->isEnabled());
    }

    public function test_reporter_is_disabled_without_project_key(): void
    {
        $this->app['config']->set('beacon.project_key', null);

        $reporter = new HttpErrorReporter($this->app['config']->get('beacon'));

        $this->assertFalse($reporter->isEnabled());
    }

    public function test_should_not_report_ignored_exceptions(): void
    {
        $reporter = $this->app->make(ErrorReporterInterface::class);

        $validationException = ValidationException::withMessages(['field' => 'error']);

        $this->assertFalse($reporter->shouldReport($validationException));
    }

    public function test_should_report_regular_exceptions(): void
    {
        $reporter = $this->app->make(ErrorReporterInterface::class);

        $exception = new RuntimeException('Test error');

        $this->assertTrue($reporter->shouldReport($exception));
    }

    public function test_can_ignore_exception_at_runtime(): void
    {
        $reporter = $this->app->make(ErrorReporterInterface::class);

        $exception = new RuntimeException('Test error');

        $this->assertTrue($reporter->shouldReport($exception));

        $reporter->ignore(RuntimeException::class);

        $this->assertFalse($reporter->shouldReport($exception));
    }
}

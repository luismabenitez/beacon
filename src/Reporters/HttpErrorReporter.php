<?php

namespace Luismabenitez\Beacon\Reporters;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Luismabenitez\Beacon\Contracts\ErrorReporterInterface;
use Luismabenitez\Beacon\Support\ErrorContextBuilder;
use Throwable;

class HttpErrorReporter implements ErrorReporterInterface
{
    /** @var array */
    protected $config;

    /** @var ErrorContextBuilder */
    protected $contextBuilder;

    /** @var array */
    protected $ignoredExceptions;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->contextBuilder = new ErrorContextBuilder($config);
        $this->ignoredExceptions = $config['ignored_exceptions'] ?? [];
    }

    /**
     * Report an exception to the Beacon central server.
     */
    public function report(Throwable $exception, array $context = []): void
    {
        if (! $this->isEnabled() || ! $this->shouldReport($exception)) {
            return;
        }

        try {
            $payload = $this->contextBuilder->build($exception, $context);
            $this->sendReport($payload);
        } catch (Throwable $e) {
            // Never let Beacon break the application
            Log::warning('Beacon: Failed to send error report', [
                'error' => $e->getMessage(),
                'original_exception' => get_class($exception),
            ]);
        }
    }

    /**
     * Check if Beacon reporting is enabled.
     */
    public function isEnabled(): bool
    {
        if (! ($this->config['enabled'] ?? false)) {
            return false;
        }

        if (empty($this->config['project_key'])) {
            return false;
        }

        if (empty($this->config['endpoint'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if the exception should be reported.
     */
    public function shouldReport(Throwable $exception): bool
    {
        foreach ($this->ignoredExceptions as $ignoredClass) {
            if ($exception instanceof $ignoredClass) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send the error report to the Beacon server.
     */
    protected function sendReport(array $payload): void
    {
        $endpoint = $this->config['endpoint'];
        $timeout = $this->config['http']['timeout'] ?? 5;
        $retryTimes = $this->config['http']['retry_times'] ?? 2;
        $retrySleep = $this->config['http']['retry_sleep'] ?? 100;

        try {
            $response = Http::timeout($timeout)
                ->retry($retryTimes, $retrySleep)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Beacon-Key' => $this->config['project_key'],
                ])
                ->post($endpoint, $payload);

            if ($response->failed()) {
                Log::warning('Beacon: Server returned error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Beacon: HTTP request failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Add an exception class to the ignore list at runtime.
     */
    public function ignore(string $exceptionClass): self
    {
        $this->ignoredExceptions[] = $exceptionClass;

        return $this;
    }

    /**
     * Get the context builder instance.
     */
    public function getContextBuilder(): ErrorContextBuilder
    {
        return $this->contextBuilder;
    }

    /**
     * Register Beacon with Laravel's exception handler (Laravel 11+).
     */
    public function handles(\Illuminate\Foundation\Configuration\Exceptions $exceptions): void
    {
        $exceptions->reportable(function (Throwable $exception) {
            $this->report($exception);
        });
    }
}

<?php

namespace Luismabenitez\Beacon\Contracts;

use Throwable;

interface ErrorReporterInterface
{
    /**
     * Report an exception to the Beacon central server.
     *
     * @param Throwable $exception The exception to report
     * @param array<string, mixed> $context Additional context data
     * @return void
     */
    public function report(Throwable $exception, array $context = []): void;

    /**
     * Check if reporting is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Check if the given exception should be reported.
     *
     * @param Throwable $exception
     * @return bool
     */
    public function shouldReport(Throwable $exception): bool;
}

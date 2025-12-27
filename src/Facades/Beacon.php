<?php

namespace Luismabenitez\Beacon\Facades;

use Illuminate\Support\Facades\Facade;
use Luismabenitez\Beacon\Contracts\ErrorReporterInterface;

/**
 * @method static void report(\Throwable $exception, array $context = [])
 * @method static void handles(\Illuminate\Foundation\Configuration\Exceptions $exceptions)
 * @method static bool isEnabled()
 * @method static bool shouldReport(\Throwable $exception)
 * @method static \Luismabenitez\Beacon\Reporters\HttpErrorReporter ignore(string $exceptionClass)
 *
 * @see \Luismabenitez\Beacon\Reporters\HttpErrorReporter
 */
class Beacon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ErrorReporterInterface::class;
    }
}

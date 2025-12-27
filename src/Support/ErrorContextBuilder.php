<?php

namespace Luismabenitez\Beacon\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ErrorContextBuilder
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Build the complete error payload for reporting.
     *
     * @param Throwable $exception
     * @param array<string, mixed> $extraContext
     * @return array<string, mixed>
     */
    public function build(Throwable $exception, array $extraContext = []): array
    {
        return [
            'project_key' => $this->config['project_key'] ?? null,
            'environment' => $this->config['environment'] ?? 'unknown',
            'release' => $this->config['release'] ?? null,
            'exception' => $this->buildExceptionData($exception),
            'request' => $this->buildRequestData(),
            'user' => $this->buildUserData(),
            'server' => $this->buildServerData(),
            'context' => [
                'extra' => $extraContext,
                'tags' => $this->extractTags($extraContext),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build exception-specific data.
     */
    protected function buildExceptionData(Throwable $exception): array
    {
        return [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stacktrace' => $this->formatStacktrace($exception),
            'previous' => $exception->getPrevious()
                ? $this->buildExceptionData($exception->getPrevious())
                : null,
        ];
    }

    /**
     * Format the exception stacktrace.
     */
    protected function formatStacktrace(Throwable $exception): array
    {
        $frames = [];

        foreach ($exception->getTrace() as $index => $frame) {
            $frames[] = [
                'index' => $index,
                'file' => $frame['file'] ?? '[internal]',
                'line' => $frame['line'] ?? 0,
                'class' => $frame['class'] ?? null,
                'function' => $frame['function'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }

        return $frames;
    }

    /**
     * Build request data if available.
     */
    protected function buildRequestData(): ?array
    {
        if (! ($this->config['context']['send_request'] ?? true)) {
            return null;
        }

        if (! App::runningInConsole() && app()->has('request')) {
            $request = app('request');

            return [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $this->getHeaders($request),
                'query' => $this->getQueryParameters($request),
                'payload' => $this->getPayload($request),
            ];
        }

        // Console context
        if (App::runningInConsole()) {
            return [
                'url' => null,
                'method' => 'CLI',
                'ip' => null,
                'user_agent' => 'Artisan/' . App::version(),
                'command' => $_SERVER['argv'] ?? [],
            ];
        }

        return null;
    }

    /**
     * Get request headers (filtered).
     */
    protected function getHeaders(Request $request): ?array
    {
        if (! ($this->config['context']['send_headers'] ?? true)) {
            return null;
        }

        $headers = collect($request->headers->all())
            ->map(fn ($values) => implode(', ', $values))
            ->toArray();

        return $this->redactSensitiveData($headers);
    }

    /**
     * Get query parameters (filtered).
     */
    protected function getQueryParameters(Request $request): ?array
    {
        if (! ($this->config['context']['send_query'] ?? true)) {
            return null;
        }

        return $this->redactSensitiveData($request->query());
    }

    /**
     * Get request payload (filtered).
     */
    protected function getPayload(Request $request): ?array
    {
        if (! ($this->config['context']['send_payload'] ?? true)) {
            return null;
        }

        // Don't send file uploads
        $payload = $request->except(array_keys($request->allFiles()));

        return $this->redactSensitiveData($payload);
    }

    /**
     * Build authenticated user data if available.
     */
    protected function buildUserData(): ?array
    {
        if (! ($this->config['context']['send_user'] ?? true)) {
            return null;
        }

        try {
            $user = Auth::user();

            if ($user) {
                return [
                    'id' => $user->getAuthIdentifier(),
                    'email' => $user->email ?? null,
                    'name' => $user->name ?? null,
                ];
            }
        } catch (Throwable) {
            // Auth might not be available, ignore
        }

        return null;
    }

    /**
     * Build server/environment data.
     */
    protected function buildServerData(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => App::version(),
            'app_name' => config('app.name'),
            'hostname' => gethostname() ?: null,
            'os' => PHP_OS,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Extract tags from extra context.
     */
    protected function extractTags(array $context): array
    {
        return Arr::wrap($context['tags'] ?? []);
    }

    /**
     * Redact sensitive data from an array.
     */
    protected function redactSensitiveData(array $data): array
    {
        $redactFields = $this->config['redact_fields'] ?? [];

        return collect($data)->map(function ($value, $key) use ($redactFields) {
            foreach ($redactFields as $field) {
                if (stripos($key, $field) !== false) {
                    return '[REDACTED]';
                }
            }

            if (is_array($value)) {
                return $this->redactSensitiveData($value);
            }

            return $value;
        })->toArray();
    }
}

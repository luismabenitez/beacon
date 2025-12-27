<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Beacon Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether Beacon error reporting is active.
    | Set to false to completely disable error reporting.
    |
    */
    'enabled' => env('BEACON_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Project Key
    |--------------------------------------------------------------------------
    |
    | The unique key that identifies your project in the Beacon panel.
    | Obtain this from the Beacon central dashboard when creating a project.
    |
    */
    'project_key' => env('BEACON_PROJECT_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoint
    |--------------------------------------------------------------------------
    |
    | The URL of the Beacon central server that receives error reports.
    |
    */
    'endpoint' => env('BEACON_ENDPOINT', 'https://errors.rocketfy.internal/api/error-monitor/report'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | The environment name to report. Defaults to the Laravel app environment.
    |
    */
    'environment' => env('BEACON_ENV', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Release Version
    |--------------------------------------------------------------------------
    |
    | Optional release/version identifier for your application.
    | Useful for tracking which version introduced a bug.
    |
    */
    'release' => env('BEACON_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    |
    | Exception classes that should NOT be reported to Beacon.
    | Add any exceptions you want to ignore here.
    |
    */
    'ignored_exceptions' => [
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        Illuminate\Auth\AuthenticationException::class,
        Illuminate\Session\TokenMismatchException::class,
        Illuminate\Validation\ValidationException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Options
    |--------------------------------------------------------------------------
    |
    | Control what contextual information is sent with error reports.
    |
    */
    'context' => [
        'send_user' => env('BEACON_SEND_USER', true),
        'send_request' => env('BEACON_SEND_REQUEST', true),
        'send_headers' => env('BEACON_SEND_HEADERS', true),
        'send_payload' => env('BEACON_SEND_PAYLOAD', true),
        'send_query' => env('BEACON_SEND_QUERY', true),
        'send_session' => env('BEACON_SEND_SESSION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should be redacted from request data and headers.
    | Values will be replaced with '[REDACTED]'.
    |
    */
    'redact_fields' => [
        'password',
        'password_confirmation',
        'secret',
        'token',
        'api_key',
        'authorization',
        'cookie',
        'credit_card',
        'card_number',
        'cvv',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Configuration for the HTTP client that sends reports.
    |
    */
    'http' => [
        'timeout' => env('BEACON_TIMEOUT', 5),
        'retry_times' => env('BEACON_RETRY_TIMES', 2),
        'retry_sleep' => env('BEACON_RETRY_SLEEP', 100), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Send error reports via queue for better performance.
    | Set to null to send synchronously.
    |
    */
    'queue' => [
        'enabled' => env('BEACON_QUEUE_ENABLED', false),
        'connection' => env('BEACON_QUEUE_CONNECTION'),
        'queue' => env('BEACON_QUEUE_NAME', 'beacon'),
    ],
];

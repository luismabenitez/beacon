# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Beacon is a Laravel package for error monitoring and reporting - an internal Flare alternative for Rocketfy. It captures exceptions and sends them to a central server for aggregation and analysis.

## Package Structure

```
src/
├── BeaconServiceProvider.php    # Service registration and config publishing
├── Contracts/
│   └── ErrorReporterInterface.php
├── Facades/
│   └── Beacon.php               # Facade for Beacon::report()
├── Jobs/
│   └── SendErrorReportJob.php   # Async queue job
├── Reporters/
│   └── HttpErrorReporter.php    # Main reporter implementation
└── Support/
    └── ErrorContextBuilder.php  # Builds error payload with context
config/
└── beacon.php                   # Package configuration
```

## Key Architecture

- **Namespace**: `Luismabenitez\Beacon`
- **Service binding**: `ErrorReporterInterface` resolves to `HttpErrorReporter`
- **Facade accessor**: `beacon` or `ErrorReporterInterface::class`
- **Auto-discovery**: Configured in `composer.json` extra.laravel section

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit

# Check code style (if installed)
./vendor/bin/pint
```

## Distribution

This is a private Composer package distributed via GitHub VCS, not Packagist:

```json
{
    "repositories": [{"type": "vcs", "url": "git@github.com:luismabenitez/beacon.git"}],
    "require": {"luismabenitez/beacon": "^1.0"}
}
```

## Main API

```php
// Report an exception
Beacon::report($exception, ['extra' => 'context']);

// Check if enabled
Beacon::isEnabled();

// Ignore exception type at runtime
Beacon::ignore(SomeException::class);
```

## Configuration Keys

Primary env vars: `BEACON_ENABLED`, `BEACON_PROJECT_KEY`, `BEACON_ENDPOINT`, `BEACON_ENV`, `BEACON_RELEASE`

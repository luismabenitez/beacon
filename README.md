# Beacon

Paquete Laravel para monitoreo y reporte de errores - Alternativa interna a Flare para Rocketfy.

## Instalación

### 1. Agregar el repositorio privado en `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:luismabenitez/beacon.git"
        }
    ],
    "require": {
        "luismabenitez/beacon": "^1.0"
    }
}
```

### 2. Instalar el paquete:

```bash
composer require luismabenitez/beacon
```

### 3. Publicar la configuración:

```bash
php artisan vendor:publish --tag=beacon-config
```

### 4. Configurar variables de entorno:

```env
BEACON_ENABLED=true
BEACON_PROJECT_KEY=tu-project-key-aqui
BEACON_ENDPOINT=https://errors.rocketfy.internal/api/error-monitor/report
BEACON_ENV=production
BEACON_RELEASE=v1.0.0
```

### 5. Integrar con el Exception Handler:

**Laravel 11+ (bootstrap/app.php):**

```php
use Luismabenitez\Beacon\Facades\Beacon;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (Throwable $e) {
        if (app()->bound('beacon')) {
            Beacon::report($e);
        }
    });
})
```

**Laravel 10 (app/Exceptions/Handler.php):**

```php
use Luismabenitez\Beacon\Facades\Beacon;

public function register(): void
{
    $this->reportable(function (Throwable $e) {
        if (app()->bound('beacon')) {
            Beacon::report($e);
        }
    });
}
```

## Uso

### Reporte manual de excepciones:

```php
use Luismabenitez\Beacon\Facades\Beacon;

try {
    // código que puede fallar
} catch (Exception $e) {
    Beacon::report($e, [
        'order_id' => $order->id,
        'tags' => ['checkout', 'payment'],
    ]);
}
```

### Ignorar excepciones en runtime:

```php
Beacon::ignore(MyCustomException::class);
```

## Configuración

Ver `config/beacon.php` para todas las opciones disponibles:

- `enabled`: Activar/desactivar el reporte
- `project_key`: Clave única del proyecto
- `endpoint`: URL del servidor central Beacon
- `environment`: Ambiente (production, staging, local)
- `release`: Versión de la aplicación
- `ignored_exceptions`: Clases de excepciones a ignorar
- `context.*`: Control de qué información enviar
- `redact_fields`: Campos sensibles a ocultar
- `http.*`: Configuración del cliente HTTP
- `queue.*`: Configuración para envío asíncrono

---

## Checklist de Onboarding

- [ ] Crear proyecto en el panel central de Beacon
- [ ] Obtener `project_key`
- [ ] Configurar variables de entorno (`BEACON_*`)
- [ ] Instalar paquete via Composer
- [ ] Publicar y revisar config de Beacon
- [ ] Integrar con Exception Handler
- [ ] Lanzar excepción de prueba y verificar en el panel

---

## Diseño del Servidor Central

### Endpoint Principal

```
POST /api/error-monitor/report
Header: X-Beacon-Key: {project_key}
```

### Estructura del Payload

```json
{
    "project_key": "uuid-del-proyecto",
    "environment": "production",
    "release": "v1.2.3",
    "exception": {
        "class": "RuntimeException",
        "message": "Something went wrong",
        "code": 0,
        "file": "/var/www/app/Services/Example.php",
        "line": 123,
        "stacktrace": [
            {"index": 0, "file": "...", "line": 123, "class": "...", "function": "..."}
        ],
        "previous": null
    },
    "request": {
        "url": "https://example.com/api/orders",
        "method": "POST",
        "ip": "1.2.3.4",
        "user_agent": "Mozilla/5.0...",
        "headers": {"accept": "application/json"},
        "query": {},
        "payload": {"order_id": 123}
    },
    "user": {
        "id": 1,
        "email": "user@example.com",
        "name": "John Doe"
    },
    "server": {
        "php_version": "8.2.0",
        "laravel_version": "11.0.0",
        "app_name": "My App",
        "hostname": "web-01",
        "os": "Linux",
        "memory_usage": 12345678,
        "peak_memory": 23456789
    },
    "context": {
        "extra": {"custom_data": "value"},
        "tags": ["checkout", "critical"]
    },
    "timestamp": "2024-01-15T10:30:00+00:00"
}
```

### Esquema de Base de Datos

```sql
-- Proyectos registrados
CREATE TABLE projects (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    project_key CHAR(36) UNIQUE NOT NULL,  -- UUID
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Grupos de errores (agrupados por fingerprint)
CREATE TABLE error_groups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    fingerprint CHAR(64) NOT NULL,  -- SHA-256 de class+file+line
    exception_class VARCHAR(255) NOT NULL,
    message_sample TEXT,
    first_seen_at TIMESTAMP NOT NULL,
    last_seen_at TIMESTAMP NOT NULL,
    occurrences_count INT UNSIGNED DEFAULT 1,
    status ENUM('unresolved', 'resolved', 'ignored') DEFAULT 'unresolved',
    UNIQUE KEY (project_id, fingerprint),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Ocurrencias individuales
CREATE TABLE error_occurrences (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    error_group_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    environment VARCHAR(50),
    payload JSON NOT NULL,
    seen_at TIMESTAMP NOT NULL,
    INDEX (seen_at),
    FOREIGN KEY (error_group_id) REFERENCES error_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### Generación de Fingerprint

```php
$fingerprint = hash('sha256', implode('|', [
    $payload['exception']['class'],
    $payload['exception']['file'],
    $payload['exception']['line'],
]));
```

## Licencia

Propietario - Rocketfy © 2024

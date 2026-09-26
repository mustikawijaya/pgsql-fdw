# Laravel Postgres FDW

[![Latest Version](https://img.shields.io/packagist/v/mustikawijaya/pgsql-fdw.svg?style=flat-square)](https://packagist.org/packages/mustikawijaya/pgsql-fdw)
[![Total Downloads](https://img.shields.io/packagist/dt/mustikawijaya/pgsql-fdw.svg?style=flat-square)](https://packagist.org/packages/mustikawijaya/pgsql-fdw)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

**Laravel Postgres FDW** is an open-source Laravel package designed to simplify, automate, and monitor PostgreSQL Foreign Data Wrappers (`postgres_fdw`).

It uses Laravel's native database configuration (`config/database.php`) as the single source of truth for defining source and destination connections.

---

## Features

- **Automated FDW Preparation (`fdw:prepare`)**: Configures PostgreSQL extension, foreign server, user mapping, permissions, and schema namespace automatically.
- **Schema Isolation**: Prevents table collision by isolating foreign tables into dedicated PostgreSQL schemas (e.g., `fdw_pgsql_pusat.users`).
- **Idempotent SQL Scaffolding (`fdw:make-sql`)**: Generates version-controllable raw `.sql` files organized hierarchically: `{dest}/{source}/{table}.sql`.
- **Native Schema Import (`fdw:import-schema`)**: Bulk registers entire schemas directly without filesystem clutter.
- **Automated Deployments (`fdw:migrate`)**: Safely executes idempotent DDL migrations on destination databases.
- **Status Monitoring (`fdw:monitor`)**: Interactive CLI dashboard displaying registered foreign servers, user mappings, and foreign tables.
- **Clean Teardown (`fdw:rollback`)**: Safely drops specific foreign tables or entire foreign servers.

---

## Requirements

- **PHP:** `^8.1 || ^8.2 || ^8.3 || ^8.4`
- **Laravel Framework:** `^10.0 || ^11.0 || ^12.0 || ^13.0+`
- **Database:** PostgreSQL `>= 11.0` (with `postgres_fdw` extension available)

---

## Installation

Install the package via Composer:

```bash
composer require mustikawijaya/pgsql-fdw
```

Publish the package configuration:

```bash
php artisan vendor:publish --tag="pgsql-fdw-config"
```

---

## Configuration

The configuration file is published to `config/pgsql-fdw.php`:

```php
return [
    'sql_output_path' => database_path('fdw'),
    'use_schema_isolation' => true,
    'schema_prefix' => 'fdw_',
    'default_schema' => env('DB_FDW_SCHEMA', 'public'),

    'options' => [
        'server' => [
            'use_remote_estimate' => 'true',
            'fetch_size' => '2000',
            'connect_timeout' => '10',
        ],
        'table' => [
            'updatable' => 'false', // Read-only by default
        ],
    ],

    'log_channel' => env('FDW_LOG_CHANNEL', 'single'),
];
```

---

## Usage & Artisan Commands

### 1. Prepare Destination Database
```bash
php artisan fdw:prepare --source=pgsql_central --dest=pgsql_local
```

### 2. Scaffold DDL SQL Files
```bash
# Specific table
php artisan fdw:make-sql --source=pgsql_central --dest=pgsql_local --table=users

# All tables in source schema
php artisan fdw:make-sql --source=pgsql_central --dest=pgsql_local --all
```

### 3. Native Schema Import (Alternative fast path)
```bash
php artisan fdw:import-schema --source=pgsql_central --dest=pgsql_local --into-schema=fdw_pgsql_central
```

### 4. Deploy Migrations
```bash
# Preview files first
php artisan fdw:migrate --dest=pgsql_local --dry-run

# Execute migrations
php artisan fdw:migrate --dest=pgsql_local
```

### 5. Monitor FDW Health & Tables
```bash
php artisan fdw:monitor --dest=pgsql_local
```

### 6. Rollback / Teardown
```bash
# Drop a specific foreign table
php artisan fdw:rollback --dest=pgsql_local --table=users --schema=fdw_pgsql_central --force

# Teardown an entire foreign server
php artisan fdw:rollback --dest=pgsql_local --server=fdw_server_pgsql_central --force
```

---

## Testing

Run the test suite using PHPUnit:

```bash
./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, development setup, and pull request guidelines.

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.


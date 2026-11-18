# Laravel Postgres FDW

[![Latest Version](https://img.shields.io/packagist/v/mustikawijaya/pgsql-fdw.svg?style=flat-square)](https://packagist.org/packages/mustikawijaya/pgsql-fdw)
[![Total Downloads](https://img.shields.io/packagist/dt/mustikawijaya/pgsql-fdw.svg?style=flat-square)](https://packagist.org/packages/mustikawijaya/pgsql-fdw)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

**Laravel Postgres FDW** is an open-source Laravel package designed to simplify, automate, and monitor PostgreSQL Foreign Data Wrappers (`postgres_fdw`).

It uses Laravel's native database configuration (`config/database.php`) as the single source of truth for defining source and destination connections.

---

## Features

- **Automated FDW Preparation (`fdw:prepare`)**: Configures PostgreSQL extension, foreign server, user mapping, permissions, and schema namespace automatically.
- **Schema Isolation**: Prevents table collision by isolating foreign tables into dedicated PostgreSQL schemas (e.g., `fdw_pgsql_central.users`).
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

> [!TIP]
> All executed DDL queries (success and errors) are logged to the configured `log_channel` with passwords automatically redacted (`password '********'`). Set `FDW_LOG_CHANNEL=none` or `null` to disable DDL logging.

---

## Usage & Artisan Commands

### 1. `fdw:prepare`
Sets up the FDW environment on the destination database, including the `postgres_fdw` extension, Foreign Server, User Mapping, `GRANT USAGE` permissions, and isolation schema.

#### Options & Parameters:
| Option | Status | Default | Description |
| :--- | :--- | :--- | :--- |
| `--source=` | **Required** | `-` | Source connection name defined in `config/database.php`. |
| `--dest=` | **Required** | `-` | Destination connection name defined in `config/database.php`. |
| `--force` | Optional | `false` | Drops and recreates the foreign server (`DROP SERVER ... CASCADE`), clearing stale user mappings and attached foreign tables before preparing. |

#### Usage Examples:
```bash
# Standard preparation
php artisan fdw:prepare --source=pgsql_central --dest=pgsql_local

# Reset stale server/mappings after database restore (drops attached foreign tables via CASCADE)
php artisan fdw:prepare --source=pgsql_central --dest=pgsql_local --force
```

---

### 2. `fdw:make-sql`
Generates idempotent raw `.sql` DDL files (`DROP FOREIGN TABLE IF EXISTS` + `CREATE FOREIGN TABLE`) preserving exact PostgreSQL data types (numeric precision, timestamps, arrays, and UDTs). Files are organized hierarchically at `{sql_output_path}/{dest}/{source}/{table}.sql`.

#### Options & Parameters:
| Option | Status | Default | Description |
| :--- | :--- | :--- | :--- |
| `--source=` | **Required** | `-` | Source connection name defined in `config/database.php`. |
| `--dest=` | **Required** | `-` | Destination connection name defined in `config/database.php`. |
| `--table=` | Optional* | `-` | Specific remote table to scaffold (*Required if `--all` is not passed). |
| `--all` | Optional* | `false` | Scaffold all tables in the source schema (*Required if `--table=` is not passed). |

#### Usage Examples:
```bash
# Scaffold a single table
php artisan fdw:make-sql --source=pgsql_central --dest=pgsql_local --table=users

# Scaffold all tables in the source schema simultaneously
php artisan fdw:make-sql --source=pgsql_central --dest=pgsql_local --all
```

---

### 3. `fdw:import-schema`
Bulk imports an entire foreign schema natively using PostgreSQL's `IMPORT FOREIGN SCHEMA`. This provides a fast-path alternative without creating `.sql` files on disk.

#### Options & Parameters:
| Option | Status | Default | Description |
| :--- | :--- | :--- | :--- |
| `--source=` | **Required** | `-` | Source connection name defined in `config/database.php`. |
| `--dest=` | **Required** | `-` | Destination connection name defined in `config/database.php`. |
| `--remote-schema=` | Optional | `public` (or source config) | Remote schema to import from. |
| `--into-schema=` | Optional | Isolated schema (`fdw_{source}`) | Local destination schema where foreign tables are registered. |

#### Usage Examples:
```bash
# Default import into the configured isolation schema (e.g. fdw_pgsql_central)
php artisan fdw:import-schema --source=pgsql_central --dest=pgsql_local

# Import from a non-public remote schema (e.g. analytics)
php artisan fdw:import-schema --source=pgsql_central --dest=pgsql_local --remote-schema=analytics

# Import into a custom local schema namespace
php artisan fdw:import-schema --source=pgsql_central --dest=pgsql_local --into-schema=custom_fdw_schema

# Custom remote schema and target schema combined
php artisan fdw:import-schema --source=pgsql_central --dest=pgsql_local --remote-schema=inventory --into-schema=fdw_inventory
```

---

### 4. `fdw:migrate`
Executes generated DDL `.sql` migration files on the destination database sequentially and safely.

#### Options & Parameters:
| Option | Status | Default | Description |
| :--- | :--- | :--- | :--- |
| `--dest=` | **Required** | `-` | Destination connection name defined in `config/database.php`. |
| `--dry-run` | Optional | `false` | Previews the list of `.sql` files that would be executed without running them. |

#### Usage Examples:
```bash
# Preview migrations without executing (--dry-run)
php artisan fdw:migrate --dest=pgsql_local --dry-run

# Execute all pending FDW migrations
php artisan fdw:migrate --dest=pgsql_local
```

---

### 5. `fdw:monitor`
Displays an interactive status dashboard in the console showing Foreign Servers, User Mappings, and Foreign Tables registered on the destination database.

#### Options & Parameters:
| Option | Status | Default | Description |
| :--- | :--- | :--- | :--- |
| `--dest=` | **Required** | `-` | Destination connection name defined in `config/database.php`. |

#### Usage Examples:
```bash
# Inspect FDW dashboard on destination database
php artisan fdw:monitor --dest=pgsql_local
```

---

### 6. `fdw:rollback`
Safely drops foreign tables or foreign servers from the destination database.

> [!NOTE]
> Physical tables and data on the remote source database are **100% untouched and safe**. Only the proxy pointers on the destination database are dropped.

#### Options & Parameters:
| Option | Status | Default | Description |
| :--- | :--- | :--- | :--- |
| `--dest=` | **Required** | `-` | Destination connection name defined in `config/database.php`. |
| `--table=` | Optional* | `-` | Name of the foreign table to drop (*Required if `--server=` is not passed). |
| `--schema=` | Optional | `public` (or default schema) | Schema where the foreign table resides. |
| `--server=` | Optional* | `-` | Foreign server to drop completely (*Required if `--table=` is not passed). |
| `--cascade` | Optional | `false` | Drops the object and any dependent objects (e.g. local views or foreign tables). |
| `--force` | Optional | `false` | Bypasses interactive confirmation prompt (ideal for CI/CD automation). |

#### Usage Examples:
```bash
# Drop a single table with interactive confirmation
php artisan fdw:rollback --dest=pgsql_local --table=users --schema=fdw_pgsql_central

# Drop a table non-interactively in automation/scripts (--force)
php artisan fdw:rollback --dest=pgsql_local --table=users --schema=fdw_pgsql_central --force

# Drop a table along with dependent local views (--cascade)
php artisan fdw:rollback --dest=pgsql_local --table=orders --schema=fdw_pgsql_central --cascade --force

# Teardown an entire foreign server
php artisan fdw:rollback --dest=pgsql_local --server=fdw_server_pgsql_central --force

# Teardown a foreign server along with ALL its foreign tables and user mappings (--cascade)
php artisan fdw:rollback --dest=pgsql_local --server=fdw_server_pgsql_central --cascade --force
```

---

### 7. Querying Remote Foreign Tables in Laravel
Once foreign tables are migrated, you can query them directly using Laravel's Query Builder or Eloquent models:

```php
use Illuminate\Support\Facades\DB;

// Query via DB Facade
$users = DB::table('fdw_pgsql_central.users')->where('status', 'active')->get();

// Query via Eloquent Model
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentralUser extends Model
{
    protected $table = 'fdw_pgsql_central.users';
    public $timestamps = false;
}

$recentUsers = CentralUser::latest()->take(10)->get();
```

---

## Testing

Run the test suite using PHPUnit:

```bash
./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, development setup, and pull request guidelines.

## Security Best Practices

When configuring remote PostgreSQL connections for Foreign Data Wrappers, follow the **Principle of Least Privilege**:

1. **Dedicated Read-Only Role**: Avoid using superuser (e.g. `postgres`) credentials for FDW mappings. Create a dedicated database user with read-only access to only the necessary schemas and tables:
   ```sql
   -- On remote PostgreSQL server:
   CREATE ROLE fdw_reader WITH LOGIN PASSWORD 'strong_password';
   GRANT USAGE ON SCHEMA public TO fdw_reader;
   GRANT SELECT ON ALL TABLES IN SCHEMA public TO fdw_reader;
   ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO fdw_reader;
   ```
2. **Network Isolation**: Restrict remote PostgreSQL port access (`5432`) to the IP address or VPC subnet of your application servers via `pg_hba.conf` and firewall rules.
3. **Environment Secrets**: Store connection credentials exclusively in `.env` and never commit passwords to version control.

## Security Vulnerabilities

Please review [our security policy](SECURITY.md) on how to report security vulnerabilities responsibly.

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.


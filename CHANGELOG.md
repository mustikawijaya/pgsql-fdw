# Changelog

All notable changes to `mustikawijaya/pgsql-fdw` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-09-26

### Fixed
- Fixed `TypeError` in `fdw:monitor` command by explicitly converting database query `stdClass` objects into arrays for Symfony Console table rendering.
- Added CONTRIBUTING.md guidelines.

## [1.0.0] - 2026-09-26

### Added
- Initial release of **Laravel Postgres FDW** (`mustikawijaya/pgsql-fdw`).
- Compatibility support for PHP `^8.1 || ^8.2 || ^8.3 || ^8.4` and Laravel `^10.0 || ^11.0 || ^12.0 || ^13.0+`.
- Automated FDW preparation command `fdw:prepare` for configuring extensions, foreign servers, user mappings, grants, and schemas.
- Idempotent SQL generator command `fdw:make-sql` preserving precision for PostgreSQL data types (`varchar(n)`, `numeric(p,s)`, timestamps, arrays, and UDTs).
- Hierarchical SQL file management in `{sql_output_path}/{dest}/{source}/{table}.sql`.
- Migration deployment command `fdw:migrate` with `--dry-run` preview.
- Native foreign schema mass-import command `fdw:import-schema`.
- Health and status monitoring dashboard command `fdw:monitor`.
- Safe teardown and cleanup command `fdw:rollback`.
- Schema isolation feature (`use_schema_isolation` with `{schema_prefix}{source}` naming).
- Comprehensive test suite with PHPUnit and Orchestra Testbench (Unit & E2E Integration tests).

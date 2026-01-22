# Status: [ ] Not Complete
# Title: Scaling Strategy: SQLite to PostgreSQL Migration

## Description
Develop a long-term scaling strategy and the technical tools required to migrate the panel management database from SQLite to PostgreSQL when necessary. This ensures the panel can scale to manage hundreds of servers.

## Requirements
- **Migration Triggers**:
  - Define clear thresholds: 1,000+ managed sites, 5,000+ audit log entries/day, or database file size exceeding 2GB.
- **Database Agnostic Implementation**:
  - Review all existing queries to ensure compatibility with both SQLite and PostgreSQL (avoiding engine-specific dialect).
  - Use Laravel's Schema Builder and Eloquent exclusively.
- **Migration Tooling**:
  - Develop a command: `php artisan panel:migrate-to-pg {host} {user} {pass}`.
  - Successive steps: Dump schema -> Pipe data via chunking -> Rebuild indexes -> Update `.env`.
- **Verification Scripts**:
  - Checksum comparison between key tables (Servers, Sites) before and after.
- **Horizontal Scaling**:
  - Document how to move the database to a separate RDS/Cloud SQL instance.

## Implementation Details
### Migration Command Flow:
1. `DB::connection('pgsql')->statement('SET session_replication_role = replica');` (Disable FKs during sync)
2. Iterate through all tables in SQLite.
3. `DB::connection('sqlite')->table($table)->orderBy('id')->chunk(100, function($rows) ...)`
4. `DB::connection('pgsql')->table($table)->insert($rows);`

## Configuration
- Laravel Database Migrators
- PostgreSQL 15+

## Audit & Logging
- Log total row counts for each table transitioned.
- Log time taken per table to identify performance bottlenecks.

## Testing
- Perform a dry-run migration from a test SQLite DB (populated with 10k dummy records) to a local PostgreSQL instance.
- Verify foreign key integrity after re-enabling.

## Completion Criteria
- [ ] Scaling strategy documented in `.project/docs/scaling.md`
- [ ] Migration command `panel:migrate-to-pg` functional and tested
- [ ] Database agnostic code review completed for all Phase 1-4 tasks

# Status: [ ] Not Complete
# Title: Scaling Strategy: SQLite to PostgreSQL Migration

## Description
Develop a long-term scaling strategy and the technical tools required to migrate the panel management database from SQLite to PostgreSQL when necessary.

## Requirements
- Document SQLite capacity limits and performance "triggers" for migration (e.g., 500+ sites).
- Create a PostgreSQL migration path:
  - Tooling to export SQLite schema and data to PostgreSQL.
  - Verification scripts to ensure data integrity after migration.
- Document configuration changes required in Laravel for the transition.
- Plan multi-server deployment support (external PG database).
- Create metrics and specialized health checks to monitor "time to migrate" status.

## Configuration
- Laravel Database Migrators
- PostgreSQL Scaling Patterns

## Audit & Logging
- Migration history and error logs.
- Performance metrics pre- and post-migration.

## Testing
- Perform a dry-run migration from a large test SQLite DB to PostgreSQL.
- Verify all panel features work correctly after migration.

## Completion Criteria
- [ ] Scaling strategy documented
- [ ] Migration tooling/scripts developed
- [ ] Verification plan for migration finalized

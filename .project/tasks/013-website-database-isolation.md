# Status: [ ] Not Complete
# Title: Website Database Isolation & Provisioning

## Description
Implement the logic to provision and isolate databases (MariaDB and PostgreSQL) for each hosted website. Each site should have its own database and a dedicated user with restricted privileges.

## Requirements
- Multi-DB Engine Support:
  - **MariaDB**: Support for 10.x+.
  - **PostgreSQL**: Support for 14-16.
- Database Creation:
  - Generate unique database names based on site domain/ID (e.g., `db_wp_1`).
- User Management:
  - Create a dedicated database user for each site.
  - Generate strong, random passwords.
- Privilege Management:
  - **MariaDB**: `GRANT ALL ON db.* TO user@localhost`.
  - **PostgreSQL**: `CREATE DATABASE db OWNER user`.
- Integration into Site Creation Workflow:
  - Automatically create DB/User if selected during site creation.
  - Populate `.env` with the generated credentials.

## Implementation Details
### MariaDB Provisioning (SQL):
```sql
CREATE DATABASE IF NOT EXISTS {{ db_name }} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '{{ db_user }}'@'localhost' IDENTIFIED BY '{{ password }}';
GRANT ALL PRIVILEGES ON {{ db_name }}.* TO '{{ db_user }}'@'localhost';
FLUSH PRIVILEGES;
```

## Configuration
- MariaDB 10.11+
- PostgreSQL 15+

## Audit & Logging
- Log database creation success and any failed SQL attempts.
- Do NOT log passwords in plain text in any logs.

## Testing
- Verify that Database User A cannot access Database B.
- Verify connection from a remote PHP-FPM process using the generated credentials.

## Completion Criteria
- [ ] MariaDB provisioning operational
- [ ] PostgreSQL provisioning operational
- [ ] User isolation verified (Cross-DB access blocked)
- [ ] Auto-population of app credentials during site setup functional

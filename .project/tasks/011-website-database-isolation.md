# Status: [ ] Not Complete
# Title: Website Database Provisioning & Multi-DB Isolation

## Description
Implement the logic to provision and manage separate database instances (MariaDB or PostgreSQL) for each website, ensuring strict multi-tenant isolation.

## Requirements
- Plan per-website database isolation strategy.
- Develop abstraction layer for database creation (MariaDB vs. PostgreSQL).
- Implement automated database and user creation during site setup.
- Generate and securely store unique credentials for each site database.
- Create UI/Logic for database selection based on site type (e.g., WordPress vs. Laravel).
- Implement connection string management and mapping to site entities.

## Configuration
- MariaDB 10.x / 11.x
- PostgreSQL 15/16
- Panel-to-DB Management connection

## Audit & Logging
- Log database creation, user creation, and permission assignment.
- Track database usage and connectivity status.

## Testing
- Verify that site-specific database users cannot access other databases.
- Test connection string generation for different site frameworks.

## Completion Criteria
- [ ] Multi-DB isolation logic implemented
- [ ] MariaDB and PostgreSQL provisioning automated
- [ ] Secure credential management operational
- [ ] Database selection integrated into site creation workflow

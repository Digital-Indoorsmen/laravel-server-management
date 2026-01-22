# Status: [ ] Not Complete
# Title: External Database Support

## Description
Extend the database management system to support connecting sites to external MariaDB or PostgreSQL instances, in addition to local databases.

## Requirements
- **Remote Host Configuration**:
  - UI to define external database "Providers" (Host, Port, Admin Credentials).
  - Connection health check tool for remote providers.
- **Provisioning on Remote Hosts**:
  - Ability to create databases and users on external providers from the panel.
  - Automatic injection of remote credentials into site `.env` files.
- **Security**:
  - Support for SSH tunneling or SSL/TLS connections to remote databases.
  - Whitelist server IP on the remote provider (automated if possible via provider APIs).

## Implementation Details
- Use MySQL/PostgreSQL clients via SSH or direct PHP PDO connections to the remote host.
- Environment variable management must handle remote hosts correctly.

## Configuration
- MariaDB / MySQL / PostgreSQL Client Tools
- Laravel DB Connections

## Audit & Logging
- Log remote database provisioning events.
- Audit remote connection strings (sanitized).

## Testing
- Verify database creation on a remote MariaDB instance.
- Verify site connectivity to an external PostgreSQL database.

## Completion Criteria
- [ ] External database provider UI functional
- [ ] Remote provisioning logic operational
- [ ] SSL/TLS connection support verified

# Status: [ ] Not Complete
# Title: Site Migration Tooling

## Description
Develop tools and scripts to assist in migrating existing websites into the management panel with minimal downtime.

## Requirements
- **Generic Importer**:
  - Tool to ingest a tarball/zip and a SQL dump.
  - Mapping UI: Map incoming files and databases to a new site/user.
- **Remote Site Fetcher**:
  - Connect to a remote server via SSH/SFTP to pull site data.
  - Automatic detection of WordPress/Laravel structures.
- **Validation Suite**:
  - Post-migration health check (PHP version compatibility, permission check).
- **Temporary URL**:
  - Ability to preview the migrated site on a temporary subdomain (e.g., `site.panel.test`) before switching DNS.

## Implementation Details
- Use `rsync` for efficient file transfers.
- Automated updates of site paths in configuration files during migration.

## Configuration
- Rsync
- SFTP Clients

## Audit & Logging
- Log migration start, progress, and final status.
- Record any errors encountered during file/DB import.

## Testing
- Perform a successful migration of a sample WordPress site from a remote server.
- Verify temporary URL functionality.

## Completion Criteria
- [ ] Archive importer functional
- [ ] Remote SSH fetcher operational
- [ ] Migration health check tool integrated

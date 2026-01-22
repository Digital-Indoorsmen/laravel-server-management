# Status: [ ] Not Complete
# Title: Database Architecture Documentation & Diagrams

## Description
Document the dual-database architecture of the project (SQLite for panel management vs. MariaDB/PostgreSQL for sites) to provide clear technical guidance.

## Requirements
- Create high-level architecture diagrams showing panel DB vs. site DBs.
- Document the SQLite optimization strategy (WAL, pragmas) in a technical guide.
- Explain the logic behind separating management data from site data.
- Detail the connection string management and credential storage strategy.
- Document backup and disaster recovery plans for both database types.
- Create a visualization of the data flow between the panel and remote databases.

## Configuration
- Mermaid.js or similar for diagrams
- Markdown for documentation

## Audit & Logging
- Version control for architecture documents.

## Testing
- Peer review of documentation and diagrams for clarity and accuracy.

## Completion Criteria
- [ ] Database architecture diagrams completed
- [ ] SQLite vs. Site-DB strategy documented
- [ ] Backup and recovery plans finalized

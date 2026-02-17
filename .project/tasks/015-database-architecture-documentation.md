# Status: [x] Complete
# Title: Technical Documentation: Database & Security Architecture

## Description
Create a detailed technical reference document covering the database schema, security model (SELinux), and remote execution patterns used in the project.

## Requirements
- Document the SQLite schema with relationships and indexing strategy.
- Document the SELinux domain mapping and the rationale for each type.
- Document the "Panel to Node" communication flow (SSH -> Command -> Callback).
- Document the encryption strategy for SSH keys and environment variables.
- Include mermaid diagrams for:
  - Database Entity Relationship (ERD).
  - Process Isolation Model (SELinux Categories).
  - Provisioning Lifecycle.

## Configuration
- Markdown
- Mermaid.js

## Completion Criteria
- [x] Technical documentation completed and stored in `.project/docs/`
- [x] Diagrams accurately reflect the implementation
- [x] Document reviewed for clarity and technical accuracy


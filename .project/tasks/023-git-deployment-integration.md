# Status: [ ] Not Complete
# Title: Git Deployment & Webhook Integration

## Description
Implement the "Deployment & Release Management" module defined in the SOW (Section 7). This allows sites to be deployed directly from Git repositories (GitHub, GitLab, Bitbucket) using deploy keys and automated build scripts.

## Requirements
- **Repository Management**:
  - UI to link a site to a Git repository URL (SSH/HTTPS).
  - Branch selection (e.g., `main`, `production`).
- **Authentication**:
  - Generate a unique SSH Deployment Key (Ed25519) for each site.
  - Display the public key to the user for adding to their Git provider.
- **Deployment Script**:
  - Create a default, editable deployment script (Bash) per site.
  - Standard steps: `git pull`, `composer install`, `npm install && npm run build`, `php artisan migrate --force`.
  - Zero-downtime deployment strategy (atomic swaps) is preferred but optional for V1.
- **Triggering**:
  - **Manual**: "Deploy Now" button in the panel.
  - **Automated**: Webhook endpoint (`/api/webhooks/deploy/{site_uuid}`) to trigger deployments on push.
- **History**:
  - Log every deployment attempt, output (stdout/stderr), and status.

## Implementation Details
- **Database**: Add `repository_url`, `branch`, `deploy_script` columns to `sites` table. Create `deployments` table for logs.
- **Security**: Store deploy keys in `ssh_keys` table (linked to the site) or a dedicated column, encrypted at rest.
- **Execution**: Use `ServerConnectionService` to run the deploy script as the site's system user.

## Configuration
- Laravel Jobs (for background deployment)
- Webhook handling

## Audit & Logging
- Log deployment triggers (User vs Webhook).
- Store full deployment logs for debugging.

## Testing
- specific unit test for deployment script generation.
- Integration test: Mock a git pull operation and verify file updates.
- Verify webhook authentication/validation.

## Completion Criteria
- [ ] Git repository linking UI functional
- [ ] Deployment SSH keys generated and retrievable
- [ ] Deployment script execution (manual trigger) working
- [ ] Webhook triggers working
- [ ] Deployment history log visible in UI

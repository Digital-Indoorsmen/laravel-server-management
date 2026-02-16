# Status: [ ] Not Complete
# Title: Filesystem ACL Management

## Description
Implement Access Control List (ACL) management to securely grant Nginx access to site directories without relying on broad group permissions. This replicates the legacy `setfacl` logic.

## Requirements
- **Strict Home Permissions**:
  - Ensure user home directories (`/home/{user}`) are set to `750` (User RWX, Group RX, Other None) or stricter.
- **ACL Application**:
  - Use `setfacl` to explicitly grant the web server user (e.g., `nginx` or `httpd`) Read/Execute access to `public_html`.
  - Command: `setfacl -R -m u:nginx:rx /home/{user}/public_html`
  - Ensure defaults are set for new files: `setfacl -d -m u:nginx:rx /home/{user}/public_html`
- **Setup Script Integration**:
  - Ensure the `acl` package is installed on the server.
  - Ensure the filesystem (XFS/Ext4) is mounted with ACL support (usually default).

## Implementation Details
- Update the `SiteProvisioningService` to execute `setfacl` commands after creating the directory structure.
- Verify ACLs are supported before attempting (graceful fallback or strict requirement).

## Configuration
- ACL (`setfacl`, `getfacl`)

## Audit & Logging
- Log the output of `setfacl` commands.

## Testing
- Verify that `nginx` user can read a file in `/home/user/public_html`.
- Verify that another system user (e.g., `user2`) cannot access `/home/user/public_html` (due to 750 on parent).

## Completion Criteria
- [ ] `acl` package installed via setup script
- [ ] `setfacl` logic integrated into site provisioning
- [ ] Verification test confirms Nginx access and User isolation

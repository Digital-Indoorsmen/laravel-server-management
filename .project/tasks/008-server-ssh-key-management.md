# Status: [ ] Not Complete
# Title: Server & SSH Key Management Logic

## Description
Implement the backend logic for managing servers and SSH keys securely within the panel.

## Requirements
- SSH Key generation UI and backend (RSA/ED25519).
- Secure storage of SSH keys (with encryption at rest).
- Implement server connection testing using Laravel CLI Kit.
- Logic for root access key insertion into remote nodes via the panel.
- Implement SSH execution wrappers that transition to the correct SELinux context (`laravel_ssh_key_t`).

## Configuration
- Laravel CLI Kit
- OpenSSL / Sodium for encryption

## Audit & Logging
- Log all SSH operations and key generation events.
- Audit trail for server connectivity checks.

## Testing
- Successfully connect to a remote AlmaLinux instance.
- Verify key encryption and decryption works correctly.

## Completion Criteria
- [ ] SSH key management fully functional
- [ ] Server connectivity verification system works
- [ ] SELinux context transition for SSH commands implemented

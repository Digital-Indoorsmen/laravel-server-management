# Status: [ ] Not Complete
# Title: Server & SSH Key Management Logic

## Description
Implement the backend logic for managing servers and SSH keys securely. This includes automated key generation, secure storage, and initial connectivity verification using standard protocols.

## Requirements
- SSH Key generation UI and backend:
  - Support for `Ed25519` (preferred) and `RSA 4096`.
  - Use `php-seclib` or `ssh-keygen` via process wrapper.
- Secure storage of SSH keys:
  - Private keys MUST be encrypted at rest using Laravel's `Crypt` (AES-256-GCM).
  - Fingerprint generation for quick identification.
- Implement server connection testing using `grazulex/laravel-cli-kit`:
  - Verify connectivity via SSH (`active` status update).
  - Retrieve basic system stats (OS version, RAM, CPU) upon initial connect.
- Logic for root access key insertion:
  - Generate a specialized `setup` key for the panel.
  - Provide a manual command for users to run on the server to add the panel's key to `authorized_keys`.
- Implement SSH execution wrappers that transition to the correct SELinux context (`laravel_ssh_key_t`) if the panel itself is running in an enforcing environment.

## Implementation Details
### Laravel CLI Kit Usage:
```php
$server = Server::find($id);
$ssh = SSH::into($server->ip_address)
    ->withUser('panel')
    ->withPrivateKey($server->sshKey->private_key_decrypted);

$output = $ssh->run('hostnamectl');
```

## Configuration
- Laravel CLI Kit
- Laravel Encryption (app key based)

## Audit & Logging
- Log all SSH connection attempts (Success/Failure) in `server_logs`.
- Audit trail for key generation and retrieval.

## Testing
- Unit test for key encryption/decryption.
- Mock SSH connection to verify command assembly logic.
- Integration test with a real AlmaLinux container/VM.

## Completion Criteria
- [ ] SSH key management fully functional and encrypted
- [ ] Server connectivity verification system returns system metadata
- [ ] UI for managing multiple SSH keys per user

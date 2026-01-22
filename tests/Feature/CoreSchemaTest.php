<?php

use App\Models\SelinuxAuditLog;
use App\Models\Server;
use App\Models\Site;
use App\Models\SshKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('creates resources with ULIDs', function () {
    $sshKey = SshKey::create([
        'name' => 'Default Key',
        'public_key' => 'ssh-rsa AAA...',
        'private_key' => 'SECRET...',
        'fingerprint' => 'fp:123',
    ]);

    expect($sshKey->id)->toBeString()
        ->and(Str::isUlid($sshKey->id))->toBeTrue();

    $server = Server::create([
        'name' => 'Production Server',
        'ip_address' => '127.0.0.1',
        'hostname' => 'prod.example.com',
        'os_version' => 'alma_9',
        'ssh_key_id' => $sshKey->id,
        'status' => 'active',
    ]);

    expect($server->id)->toBeString()
        ->and(Str::isUlid($server->id))->toBeTrue();

    $site = Site::create([
        'server_id' => $server->id,
        'domain' => 'example.com',
        'document_root' => '/var/www/example.com',
        'system_user' => 'siteuser',
        'php_version' => '8.3',
        'app_type' => 'laravel',
        'status' => 'active',
    ]);

    expect($site->id)->toBeString()
        ->and(Str::isUlid($site->id))->toBeTrue();
});

it('encrypts ssh private keys', function () {
    $rawKey = '---PRIVATE KEY---';
    $sshKey = SshKey::create([
        'name' => 'Test Key',
        'public_key' => 'ssh-rsa...',
        'private_key' => $rawKey,
        'fingerprint' => 'fp:456',
    ]);

    // Check if it's decrypted on retrieval
    expect($sshKey->private_key)->toBe($rawKey);

    // Check raw database value (should be different)
    $rawDbValue = DB::table('ssh_keys')->where('id', $sshKey->id)->value('private_key');
    expect($rawDbValue)->not->toBe($rawKey);
});

it('logs model events to selinux_audit_logs', function () {
    $sshKey = SshKey::create([
        'name' => 'Audit Test Key',
        'public_key' => 'ssh-rsa...',
        'private_key' => 'secret',
        'fingerprint' => 'fp:789',
    ]);

    // Verify creator log
    $creationLog = SelinuxAuditLog::where('context->model', SshKey::class)
        ->where('context->event', 'created')
        ->first();

    expect($creationLog)->not->toBeNull()
        ->and($creationLog->log_level)->toBe('info');

    // Verify update log
    $sshKey->update(['name' => 'Updated Key Name']);

    $updateLog = SelinuxAuditLog::where('context->model', SshKey::class)
        ->where('context->event', 'updated')
        ->first();

    expect($updateLog)->not->toBeNull()
        ->and($updateLog->context['changes'])->toHaveKey('name');

    // Verify deletion log
    $sshKey->delete();

    $deletionLog = SelinuxAuditLog::where('context->model', SshKey::class)
        ->where('context->event', 'deleted')
        ->first();

    expect($deletionLog)->not->toBeNull();
});

<?php

use App\Models\SshKey;
use App\Services\SshKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('can generate an ed25519 key pair', function () {
    $service = new SshKeyService;
    $key = $service->generate('Test Key', 'ed25519');

    expect($key)->toBeInstanceOf(SshKey::class);
    expect($key->name)->toBe('Test Key');
    expect($key->public_key)->toStartWith('ssh-ed25519');
    expect($key->private_key)->toContain('OPENSSH PRIVATE KEY');
    expect($key->fingerprint)->toStartWith('SHA256:');
});

it('can generate an rsa key pair', function () {
    $service = new SshKeyService;
    $key = $service->generate('RSA Key', 'rsa');

    expect($key->public_key)->toStartWith('ssh-rsa');
    expect($key->private_key)->toContain('OPENSSH PRIVATE KEY');
});

it('calculates fingerprints correctly', function () {
    $service = new SshKeyService;
    $publicKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIPX7I8P/N/P+X7I8P/N/P+X7I8P/N/P+X7I8P/N/P+X7 test@host';

    $fingerprint = $service->calculateFingerprint($publicKey);

    expect($fingerprint)->toStartWith('SHA256:');
});

it('encrypts private keys at rest', function () {
    $service = new SshKeyService;
    $key = $service->generate('Encrypted Key');

    // Retrieve raw value from database to verify encryption
    $raw = DB::table('ssh_keys')->where('id', $key->id)->value('private_key');

    expect($raw)->not->toContain('OPENSSH PRIVATE KEY');
    expect($key->private_key)->toContain('OPENSSH PRIVATE KEY'); // Automatically decrypted by model cast
});

it('can import an existing public key', function () {
    $service = new SshKeyService;
    $publicKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIPX7I8P/N/P+X7I8P/N/P+X7I8P/N/P+X7I8P/N/P+X7 test@host';

    $key = $service->import('Imported Key', $publicKey);

    expect($key->name)->toBe('Imported Key');
    expect($key->public_key)->toBe($publicKey);
    expect($key->private_key)->toBeNull();
    expect($key->fingerprint)->toBe($service->calculateFingerprint($publicKey));
});

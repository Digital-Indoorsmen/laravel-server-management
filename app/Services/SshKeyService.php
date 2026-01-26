<?php

namespace App\Services;

use App\Models\SshKey;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\RSA;

class SshKeyService
{
    /**
     * Generate a new SSH key pair.
     */
    public function generate(string $name, string $type = 'ed25519'): SshKey
    {
        if ($type === 'ed25519') {
            $private = EC::createKey('Ed25519');
        } else {
            $private = RSA::createKey(4096);
        }

        $publicKey = $private->getPublicKey()->toString('OpenSSH');
        $privateKey = $private->toString('OpenSSH');
        $fingerprint = $this->calculateFingerprint($publicKey);

        return SshKey::create([
            'name' => $name,
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'fingerprint' => $fingerprint,
        ]);
    }

    /**
     * Import an existing public SSH key.
     */
    public function import(string $name, string $publicKey): SshKey
    {
        $fingerprint = $this->calculateFingerprint($publicKey);

        return SshKey::create([
            'name' => $name,
            'public_key' => $publicKey,
            'private_key' => null, // We don't have the private key for imports
            'fingerprint' => $fingerprint,
        ]);
    }

    /**
     * Calculate SHA256 fingerprint of an OpenSSH public key.
     */
    public function calculateFingerprint(string $publicKey): string
    {
        $parts = explode(' ', $publicKey);
        if (count($parts) < 2) {
            return 'invalid-key';
        }

        $keyData = base64_decode($parts[1]);
        $hash = hash('sha256', $keyData, true);

        return 'SHA256:'.rtrim(base64_encode($hash), '=');
    }
}

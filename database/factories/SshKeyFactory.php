<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SshKey>
 */
class SshKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word().' Key',
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI'.str()->random(32),
            'private_key' => '-----BEGIN OPENSSH PRIVATE KEY-----'.str()->random(100),
            'fingerprint' => 'SHA256:'.str()->random(43),
        ];
    }
}

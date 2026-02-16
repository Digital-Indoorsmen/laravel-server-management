<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->domainWord().' server',
            'ip_address' => $this->faker->unique()->ipv4(),
            'hostname' => $this->faker->unique()->domainName(),
            'os_version' => 'almalinux 9.4',
            'status' => 'pending',
            'web_server' => 'nginx',
        ];
    }
}

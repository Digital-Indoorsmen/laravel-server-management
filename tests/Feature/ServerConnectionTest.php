<?php

use App\Models\Server;
use App\Models\SshKey;
use App\Services\ServerConnectionService;
use phpseclib3\Net\SSH2;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('tests connection and updates server metadata', function () {
    $sshKey = SshKey::factory()->create();
    $server = Server::factory()->create([
        'ssh_key_id' => $sshKey->id,
        'status' => 'pending',
    ]);

    // Mock the SSH connection
    $sshMock = Mockery::mock(SSH2::class);
    $sshMock->shouldReceive('login')->andReturn(true);
    $sshMock->shouldReceive('exec')
        ->with("cat /etc/os-release | grep 'PRETTY_NAME' | cut -d'\"' -f2")
        ->andReturn('AlmaLinux 9.4 (Seafoam)');
    $sshMock->shouldReceive('exec')
        ->with("free -m | awk '/Mem:/ { print $2 }'")
        ->andReturn('2048');
    $sshMock->shouldReceive('exec')
        ->with('nproc')
        ->andReturn('2');
    $sshMock->shouldReceive('exec')
        ->with('uptime -p')
        ->andReturn('up 1 hour');

    // partial mock the service to return our ssh mock
    $service = Mockery::mock(ServerConnectionService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('getSshConnection')->andReturn($sshMock);

    $result = $service->testConnection($server);

    expect($result['success'])->toBeTrue();
    expect($result['metadata']['os_version'])->toBe('AlmaLinux 9.4 (Seafoam)');
    expect($result['metadata']['ram_mb'])->toBe(2048);

    $server->refresh();
    expect($server->status)->toBe('active');
    expect($server->os_version)->toBe('AlmaLinux 9.4 (Seafoam)');

    // Verify log creation
    $this->assertDatabaseHas('server_logs', [
        'server_id' => $server->id,
        'level' => 'info',
        'message' => 'Connection test successful',
    ]);
});

it('handles connection failure and logs errors', function () {
    $sshKey = SshKey::factory()->create();
    $server = Server::factory()->create([
        'ssh_key_id' => $sshKey->id,
    ]);

    $service = Mockery::mock(ServerConnectionService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('getSshConnection')->andThrow(new Exception('Connection refused'));

    $result = $service->testConnection($server);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Connection refused');

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $server->id,
        'level' => 'error',
    ]);
});

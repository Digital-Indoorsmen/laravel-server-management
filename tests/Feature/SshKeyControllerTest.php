<?php

use App\Models\SshKey;
use Inertia\Testing\AssertableInertia as Assert;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the ssh keys index page', function () {
    SshKey::factory()->count(3)->create();

    $response = $this->get(route('ssh-keys.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('SshKeys/Index')
        ->has('sshKeys', 3)
    );
});

it('can delete an ssh key', function () {
    $key = SshKey::factory()->create();

    $response = $this->delete(route('ssh-keys.destroy', $key));

    $response->assertRedirect();
    $this->assertDatabaseMissing('ssh_keys', ['id' => $key->id]);
});

it('can import an existing ssh key', function () {
    $publicKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIPX7I8P/N/P+X7I8P/N/P+X7I8P/N/P+X7I8P/N/P+X7 test@host';

    $response = $this->post(route('ssh-keys.import'), [
        'name' => 'My Imported Key',
        'public_key' => $publicKey,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('ssh_keys', [
        'name' => 'My Imported Key',
        'public_key' => $publicKey,
        'private_key' => null,
    ]);
});

it('can download a generated ssh key', function () {
    $key = SshKey::factory()->create([
        'private_key' => 'FAKE PRIVATE KEY',
    ]);

    $response = $this->get(route('ssh-keys.download', $key));

    $response->assertSuccessful();
    $response->assertHeader('Content-Disposition', 'attachment; filename="'.str($key->name)->slug().'_id_ed25519"');
    expect($response->getContent())->toBe('FAKE PRIVATE KEY');
});

it('cannot download an imported ssh key', function () {
    $key = SshKey::factory()->create([
        'private_key' => null,
    ]);

    $response = $this->get(route('ssh-keys.download', $key));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

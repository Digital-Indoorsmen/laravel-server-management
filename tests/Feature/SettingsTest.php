<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can view the settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('settings.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings')
        ->has('preferences')
    );
});

it('can update package manager preference', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('settings.update'), [
            'package_manager' => 'npm',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('user_preferences', [
        'user_id' => $user->id,
        'package_manager' => 'npm',
    ]);
});

it('validates package manager choice', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('settings.update'), [
            'package_manager' => 'invalid',
        ]);

    $response->assertSessionHasErrors(['package_manager']);
});

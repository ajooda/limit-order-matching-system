<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('logs in user with correct credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertNoContent();
    $this->assertAuthenticated();
});

it('fails to login with incorrect email', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/login', [
        'email' => 'wrong@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest();
});

it('fails to login with incorrect password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest();
});

it('validates email is required', function () {
    $response = $this->postJson('/login', [
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('validates email format', function () {
    $response = $this->postJson('/login', [
        'email' => 'not-an-email',
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('validates password is required', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/login', [
        'email' => $user->email,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('regenerates session on login', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $oldSessionId = session()->getId();

    $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $newSessionId = session()->getId();
    expect($newSessionId)->not->toBe($oldSessionId);
});

it('logs out authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/logout');

    $response->assertNoContent();
    $this->assertGuest();
});

it('invalidates session on logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/logout');

    $this->assertGuest();
});

it('requires authentication to logout', function () {
    $response = $this->postJson('/logout');

    $response->assertUnauthorized();
});

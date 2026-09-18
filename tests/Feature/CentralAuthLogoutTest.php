<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CentralAuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a personal access client for the users provider
        // so Passport::actingAs + createToken works in tests
        $clientRepo = app(ClientRepository::class);
        $clientRepo->createPersonalAccessGrantClient(
            name: 'Testing Personal Access Client',
            provider: 'users'
        );
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@library.test',
            'password' => bcrypt('password'),
        ]);

        Passport::actingAs($user);
        $token = $user->createToken('test-token')->token;

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->id,
            'revoked' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->access_token,
            'Accept' => 'application/json',
        ])->post('/api/central/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->id,
            'revoked' => true,
        ]);
    }

    public function test_logout_without_token_returns_unauthenticated(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post('/api/central/logout');

        $response->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_logout_after_token_already_revoked_still_succeeds(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $token = $user->createToken('test-token')->token;

        $token->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->access_token,
            'Accept' => 'application/json',
        ])->post('/api/central/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);
    }
}
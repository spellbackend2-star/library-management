<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AuthController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TenantAuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $clientRepo = app(ClientRepository::class);
        $clientRepo->createPersonalAccessGrantClient(
            name: 'Testing Personal Access Client',
            provider: 'users'
        );
    }

    public function test_tenant_logout_revokes_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'tenant-admin@library.test',
            'password' => bcrypt('password'),
        ]);

        Passport::actingAs($user, [], 'api');
        $token = $user->createToken('tenant-token')->token;

        $request = Request::create('/tenant/logout', 'POST');
        $request->setUserResolver(fn () => $user);

        $response = (new AuthController())->logout($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Logged out successfully.', $response->getData()->message);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->id,
            'revoked' => true,
        ]);
    }
}

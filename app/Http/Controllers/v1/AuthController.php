<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends Controller
{
    public function login(Request $request, ServerRequestInterface $serverRequest)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $tenant = tenant();

        if (!$tenant || !$tenant->passport_client_id || !$tenant->passport_client_secret) {
            return response()->json([
                'message' => 'Tenant not found'
            ], 404);
        }

        $tokenRequest = $serverRequest->withParsedBody([
            'grant_type' => 'password',
            'client_id' => $tenant->passport_client_id,
            'client_secret' => $tenant->passport_client_secret,
            'username' => $request->email,
            'password' => $request->password,
            'scope' => '*',
        ]);

        $response = app(AccessTokenController::class)
            ->issueToken($tokenRequest, new Response());

        $result = json_decode((string) $response->getContent(), true);

        return response()->json([
            'message' => 'Successfully logged in',
            'data' => $result,
        ], 200);
    }
}

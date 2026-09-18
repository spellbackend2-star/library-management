<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function login(Request $request, ServerRequestInterface $serverRequest)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $staff = Staff::where('email', $request->email)->first();

            if ($staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
        }

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

        if ($user->staff && !$user->staff->is_active) {
            return response()->json([
                'message' => 'Staff account is inactive'
            ], 403);
        }

        if (!$user->staff) {
            $nameParts = explode(' ', $user->name, 2);

            $staff = $user->staff()->create([
                'first_name' => $nameParts[0] ?? $user->name,
                'last_name' => $nameParts[1] ?? '',
                'email' => $user->email,
                'is_active' => true,
            ]);

            $adminRole = Role::where('name', 'admin')
                ->where('guard_name', 'api')
                ->first();

            if ($adminRole) {
                $user->assignRole('admin');
            }

            app('cache')->forget(config('permission.cache.key'));
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

    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $staff = $user->staff;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'staff' => $staff ? [
                'id' => $staff->id,
                'first_name' => $staff->first_name,
                'last_name' => $staff->last_name,
                'phone' => $staff->phone,
                'email' => $staff->email,
                'is_active' => $staff->is_active,
                'hire_date' => $staff->hire_date,
            ] : null,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:255'],
        ]);

        $user->update([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
        ]);

        if ($user->staff) {
            $staffData = array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
            ], fn($v) => $v !== null);

            if (!empty($staffData)) {
                $user->staff->update($staffData);
            }
        }

        $staff = $user->fresh()->staff;

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'staff' => $staff ? [
                'id' => $staff->id,
                'first_name' => $staff->first_name,
                'last_name' => $staff->last_name,
                'phone' => $staff->phone,
                'email' => $staff->email,
                'is_active' => $staff->is_active,
                'hire_date' => $staff->hire_date,
            ] : null,
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => bcrypt($data['new_password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();

            if ($token && $token->revoke()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logged out successfully.',
                ]);
            }

            $user->tokens()
                ->where('revoked', false)
                ->get()
                ->each(fn ($token) => $token->revoke());
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\ClientRepository;
use Spatie\Permission\Models\Role;

class TenantController extends Controller{


    public function register(Request $request)
    {
        $validate = $request->validate([
            'owner' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'subdomain' => 'required|string|max:255',

        ]);

        $tenant = Tenant::create([
            'company_name' => $validate['company_name'],
            'tenant_code' => $validate['subdomain'],
            'owner_email' => $validate['email'],
        ]);

        $domain = $tenant->domains()->create([
            'domain' => $validate['subdomain'] . '.' . config('tenancy.central_domains')[0],
        ]);

        try {
            $tenant->run(function () use ($validate, $tenant) {

                $owner = \App\Models\User::create([
                    'name' => $validate['owner'],
                    'email' => $validate['email'],
                    'password' => bcrypt($validate['password']),
                ]);

                Artisan::call('db:seed', [
                    '--class' => RolePermissionSeeder::class,
                    '--force' => true,
                ]);

                $adminRole = Role::where('name', 'admin')
                    ->where('guard_name', 'api')
                    ->first();

                if ($adminRole) {
                    $owner->assignRole($adminRole->name);
                }

                $client = app(ClientRepository::class)->createPasswordGrantClient(
                    name: $validate['company_name'] . ' Password Grant Client',
                    provider: 'users',
                    confidential: true,
                );

                $tenant->update([
                    'passport_client_id' => $client->id,
                    'passport_client_secret' => $client->plainSecret,
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant registered successfully',
                'tenant' => $tenant->fresh(),
                'domain' => $domain->domain,
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create tenant: ' . $e->getMessage(),
            ], 500);
        }
    }


}

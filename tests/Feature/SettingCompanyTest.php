<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\Tenant\SettingController;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SettingCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_settings_can_be_retrieved_and_updated(): void
    {
        Setting::create([
            'group' => 'company',
            'key' => 'address',
            'value' => null,
            'type' => 'string',
            'description' => 'Company address',
            'is_locked' => false,
        ]);

        Setting::create([
            'group' => 'company',
            'key' => 'phone',
            'value' => null,
            'type' => 'string',
            'description' => 'Company phone',
            'is_locked' => false,
        ]);

        $controller = app(SettingController::class);

        $response = $controller->company();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertObjectHasProperty('address', $response->getData());
        $this->assertNull($response->getData()->address);

        $request = new Request([
            'address' => 'New Road, Kathmandu',
            'phone' => '+977-1-4567890',
            'email' => 'hello@example.com',
            'website' => 'https://example.com',
            'tax_number' => '123456789',
        ]);

        $updated = $controller->updateCompany($request);

        $this->assertSame(200, $updated->getStatusCode());
        $this->assertSame('New Road, Kathmandu', $updated->getData()->address);
        $this->assertSame('+977-1-4567890', $updated->getData()->phone);
        $this->assertSame('hello@example.com', $updated->getData()->email);
        $this->assertSame('https://example.com', $updated->getData()->website);
        $this->assertSame('123456789', $updated->getData()->tax_number);
    }
}

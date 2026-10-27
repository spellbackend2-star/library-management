<?php

namespace Tests\Unit;

use App\Services\Payments\KhaltiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class KhaltiServiceTest extends TestCase
{
    public function test_verify_returns_khalti_lookup_result(): void
    {
        config([
            'services.khalti.base_url' => 'https://dev.khalti.com/api/v2',
            'services.khalti.secret_key' => 'test-secret',
        ]);

        Http::fake([
            'https://dev.khalti.com/api/v2/epayment/lookup/' => Http::response([
                'pidx' => 'test-pidx',
                'status' => 'Completed',
                'transaction_id' => 'test-transaction',
                'total_amount' => 30000,
            ]),
        ]);

        $result = app(KhaltiService::class)->verify('test-pidx');

        $this->assertSame('Completed', $result['status']);
        $this->assertSame('test-transaction', $result['transaction_id']);
        $this->assertSame('test-pidx', $result['gateway_response']['pidx']);

        Http::assertSent(fn ($request): bool =>
            $request->url() === 'https://dev.khalti.com/api/v2/epayment/lookup/'
            && $request['pidx'] === 'test-pidx'
            && $request->hasHeader('Authorization', 'Key test-secret')
        );
    }

    public function test_verify_logs_lookup_url_and_throws_on_gateway_error(): void
    {
        config([
            'services.khalti.base_url' => 'https://dev.khalti.com/api/v2',
            'services.khalti.secret_key' => 'test-secret',
        ]);

        Http::fake([
            'https://dev.khalti.com/api/v2/epayment/lookup/' => Http::response(
                '<html>server error</html>',
                500
            ),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with(
                'KHALTI LOOKUP API FAILED',
                Mockery::on(fn (array $context): bool =>
                    $context['http_status'] === 500
                    && $context['url'] === 'https://dev.khalti.com/api/v2/epayment/lookup/'
                )
            );

        try {
            app(KhaltiService::class)->verify('test-pidx');
            $this->fail('Expected Khalti lookup failure to throw.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Khalti lookup failed with HTTP 500', $exception->getMessage());
        }
    }
}

<?php

namespace Azmolla\FraudCheckerBdCourier\Tests\Unit;

use Azmolla\FraudCheckerBdCourier\Tests\TestCase;
use Azmolla\FraudCheckerBdCourier\Services\PathaoService;
use Illuminate\Support\Facades\Http;

class PathaoServiceTest extends TestCase
{
    public function test_pathao_successful_fetch()
    {
        $phone = '01711111111';

        Http::fake([
            'https://merchant.pathao.com/api/v1/login' => Http::response([
                'access_token' => 'fake_access_token_abc'
            ], 200),

            'https://merchant.pathao.com/api/v1/user/success' => Http::response([
                'data' => [
                    'customer' => [
                        'successful_delivery' => 10,
                        'total_delivery' => 12
                    ]
                ]
            ], 200),
        ]);

        $service = new PathaoService();
        $result = $service->pathao($phone);

        $this->assertEquals([
            'success' => 10,
            'cancel' => 2,
            'total' => 12,
        ], $result);
    }
}

<?php

namespace Azmolla\FraudCheckerBdCourier\Tests\Unit;

use Azmolla\FraudCheckerBdCourier\Tests\TestCase;
use Azmolla\FraudCheckerBdCourier\Services\SteadfastService;
use Azmolla\FraudCheckerBdCourier\Config\FraudCheckerConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class SteadfastServiceTest extends TestCase
{
    public function test_steadfast_successful_fetch()
    {
        $phone = '01711111111';

        $mock = new MockHandler([
            new Response(200, [], '<input type="hidden" name="_token" value="fake_csrf_123">'),
            new Response(302, []),
            new Response(200, [], json_encode([
                'total_delivered' => 5,
                'total_cancelled' => 2,
            ])),
            new Response(200, [], '<meta name="csrf-token" content="logout_csrf_123">'),
            new Response(200, [], 'Logged out')
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $config = new FraudCheckerConfig([
            'steadfast' => [
                'user' => 'test@test.com',
                'password' => 'secret'
            ]
        ]);

        $service = new SteadfastService($config, $client);
        $result = $service->getDeliveryStats($phone);

        $this->assertEquals([
            'success' => 5,
            'cancel' => 2,
            'total' => 7,
            'success_ratio' => 71.43,
        ], $result);
    }
}

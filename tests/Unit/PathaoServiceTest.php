<?php

namespace Azmolla\FraudCheckerBdCourier\Tests\Unit;

use Azmolla\FraudCheckerBdCourier\Tests\TestCase;
use Azmolla\FraudCheckerBdCourier\Services\PathaoService;
use Azmolla\FraudCheckerBdCourier\Config\FraudCheckerConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class PathaoServiceTest extends TestCase
{
    public function test_pathao_successful_fetch()
    {
        $phone = '01711111111';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'fake_access_token_abc'
            ])),
            new Response(200, [], json_encode([
                'data' => [
                    'customer' => [
                        'successful_delivery' => 10,
                        'total_delivery' => 12
                    ]
                ]
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $config = new FraudCheckerConfig([
            'pathao' => [
                'user' => 'test@test.com',
                'password' => 'secret'
            ]
        ]);

        $pathaoService = new PathaoService($config, $client);

        // Execute function
        $result = $pathaoService->getDeliveryStats('01712345678');

        $this->assertEquals([
            'success' => 10,
            'cancel' => 2,
            'total' => 12,
            'success_ratio' => 83.33,
        ], $result);
    }
}

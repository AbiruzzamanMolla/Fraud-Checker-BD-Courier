<?php

namespace Azmolla\FraudCheckerBdCourier\Tests\Unit;

use Azmolla\FraudCheckerBdCourier\Tests\TestCase;
use Azmolla\FraudCheckerBdCourier\Services\RedxService;
use Azmolla\FraudCheckerBdCourier\Config\FraudCheckerConfig;
use Azmolla\FraudCheckerBdCourier\Cache\FileTokenCache;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class RedxServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clean up the temporary cache directory before testing
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fraud_checker_cache';
        if (is_dir($cacheDir)) {
            array_map('unlink', glob("$cacheDir/*.*"));
        }
    }

    public function test_redx_successful_fetch()
    {
        $phone = '01711111111';

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'accessToken' => 'fake_redx_token_xyz'
                ]
            ])),
            new Response(200, [], json_encode([
                'data' => [
                    'deliveredParcels' => 20,
                    'totalParcels' => 25
                ]
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $config = new FraudCheckerConfig([
            'redx' => [
                'phone' => '01722222222',
                'password' => 'secret'
            ]
        ]);
        $cache = new FileTokenCache();

        $service = new RedxService($config, $cache, $client);
        $result = $service->getDeliveryStats($phone);

        $this->assertEquals([
            'success' => 20,
            'cancel' => 5,
            'total' => 25,
            'success_ratio' => 80.0,
        ], $result);
    }
}

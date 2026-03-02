<?php

namespace Azmolla\FraudCheckerBdCourier;

use Illuminate\Support\ServiceProvider;
use Azmolla\FraudCheckerBdCourier\Services\SteadfastService;
use Azmolla\FraudCheckerBdCourier\Services\PathaoService;
use Azmolla\FraudCheckerBdCourier\Services\RedxService;

class FraudCheckerBdCourierServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish the config file on vendor:publish
        $this->publishes([
            __DIR__ . '/../config/fraud-checker-bd-courier.php' => config_path('fraud-checker-bd-courier.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/fraud-checker-bd-courier.php', 'fraud-checker-bd-courier'
        );

        $this->app->singleton('fraud-checker-bd-courier', function ($app) {
            return new class($app) {
                protected $steadfastService;
                protected $pathaoService;
                protected $redxService;

                public function __construct($app)
                {
                    $this->steadfastService = $app->make(SteadfastService::class);
                    $this->pathaoService = $app->make(PathaoService::class);
                    $this->redxService = $app->make(RedxService::class);
                }

                public function check($phoneNumber)
                {
                    return [
                        'steadfast' => $this->steadfastService->steadfast($phoneNumber),
                        'pathao' => $this->pathaoService->pathao($phoneNumber),
                        'redx' => $this->redxService->getCustomerDeliveryStats($phoneNumber),
                    ];
                }
            };
        });
    }
}

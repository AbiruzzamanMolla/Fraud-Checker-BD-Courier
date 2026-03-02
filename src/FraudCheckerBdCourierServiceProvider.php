<?php

namespace Azmolla\FraudCheckerBdCourier;

use Illuminate\Support\ServiceProvider;
use Azmolla\FraudCheckerBdCourier\Services\SteadfastService;
use Azmolla\FraudCheckerBdCourier\Services\PathaoService;
use Azmolla\FraudCheckerBdCourier\Services\RedxService;
use Azmolla\FraudCheckerBdCourier\FraudCheckerBdCourierManager;

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
            __DIR__ . '/../config/fraud-checker-bd-courier.php',
            'fraud-checker-bd-courier'
        );

        $this->app->singleton('fraud-checker-bd-courier', function ($app) {
            return new FraudCheckerBdCourierManager(
                $app->make(SteadfastService::class),
                $app->make(PathaoService::class),
                $app->make(RedxService::class)
            );
        });
    }
}

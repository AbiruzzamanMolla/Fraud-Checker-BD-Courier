<?php

namespace Azmolla\FraudCheckerBdCourier;

use Illuminate\Support\ServiceProvider;
use Azmolla\FraudCheckerBdCourier\Services\SteadfastService;
use Azmolla\FraudCheckerBdCourier\Services\PathaoService;
use Azmolla\FraudCheckerBdCourier\Services\RedxService;
use Azmolla\FraudCheckerBdCourier\FraudCheckerBdCourierManager;
use Azmolla\FraudCheckerBdCourier\Config\FraudCheckerConfig;
use Azmolla\FraudCheckerBdCourier\Cache\FileTokenCache;

/**
 * Class FraudCheckerBdCourierServiceProvider
 *
 * Registers the package services and merges configurations into the Laravel container.
 *
 * @package Azmolla\FraudCheckerBdCourier
 */
class FraudCheckerBdCourierServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish the config file on vendor:publish
        $this->publishes([
            __DIR__ . '/../config/fraud-checker-bd-courier.php' => config_path('fraud-checker-bd-courier.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/fraud-checker-bd-courier.php',
            'fraud-checker-bd-courier'
        );

        $this->app->singleton(FraudCheckerConfig::class, function ($app) {
            return new FraudCheckerConfig($app['config']->get('fraud-checker-bd-courier', []));
        });

        $this->app->singleton(FileTokenCache::class, function ($app) {
            return new FileTokenCache(storage_path('framework/cache/fraud_checker'));
        });

        $this->app->singleton(SteadfastService::class, function ($app) {
            return new SteadfastService($app->make(FraudCheckerConfig::class));
        });

        $this->app->singleton(PathaoService::class, function ($app) {
            return new PathaoService($app->make(FraudCheckerConfig::class));
        });

        $this->app->singleton(RedxService::class, function ($app) {
            return new RedxService(
                $app->make(FraudCheckerConfig::class),
                $app->make(FileTokenCache::class)
            );
        });

        $this->app->singleton('fraud-checker-bd-courier', function ($app) {
            return new FraudCheckerBdCourierManager(
                $app->make(SteadfastService::class),
                $app->make(PathaoService::class),
                $app->make(RedxService::class),
                $app->make(FraudCheckerConfig::class)
            );
        });
    }
}

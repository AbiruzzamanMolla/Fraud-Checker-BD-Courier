<?php

namespace Azmolla\FraudCheckerBdCourier\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array check(string $phoneNumber)
 */
class FraudCheckerBdCourier extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'fraud-checker-bd-courier';
    }
}

<?php

namespace Azmolla\FraudCheckerBdCourier\Contracts;

interface CourierServiceInterface
{
    /**
     * Get customer delivery statistics based on their phone number.
     *
     * @param string $phoneNumber The customer phone number.
     * @return array Returns an array with keys 'success', 'cancel', and 'total'.
     */
    public function getDeliveryStats(string $phoneNumber): array;
}

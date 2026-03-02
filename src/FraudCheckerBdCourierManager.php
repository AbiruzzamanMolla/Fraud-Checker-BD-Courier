<?php

namespace Azmolla\FraudCheckerBdCourier;

use Azmolla\FraudCheckerBdCourier\Contracts\CourierServiceInterface;

class FraudCheckerBdCourierManager
{
    public function __construct(
        protected readonly CourierServiceInterface $steadfastService,
        protected readonly CourierServiceInterface $pathaoService,
        protected readonly CourierServiceInterface $redxService,
    ) {}

    public function check(string $phoneNumber): array
    {
        return [
            'steadfast' => $this->steadfastService->getDeliveryStats($phoneNumber),
            'pathao' => $this->pathaoService->getDeliveryStats($phoneNumber),
            'redx' => $this->redxService->getDeliveryStats($phoneNumber),
        ];
    }
}

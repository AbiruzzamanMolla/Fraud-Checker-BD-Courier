<?php

namespace Azmolla\FraudCheckerBdCourier;

use Azmolla\FraudCheckerBdCourier\Services\SteadfastService;
use Azmolla\FraudCheckerBdCourier\Services\PathaoService;
use Azmolla\FraudCheckerBdCourier\Services\RedxService;

class FraudCheckerBdCourierManager
{
    public function __construct(
        protected readonly SteadfastService $steadfastService,
        protected readonly PathaoService $pathaoService,
        protected readonly RedxService $redxService,
    ) {}

    public function check(string $phoneNumber): array
    {
        return [
            'steadfast' => $this->steadfastService->steadfast($phoneNumber),
            'pathao' => $this->pathaoService->pathao($phoneNumber),
            'redx' => $this->redxService->getCustomerDeliveryStats($phoneNumber),
        ];
    }
}

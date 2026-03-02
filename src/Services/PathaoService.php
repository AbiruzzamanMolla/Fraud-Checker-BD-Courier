<?php

namespace Azmolla\FraudCheckerBdCourier\Services;

use Illuminate\Support\Facades\Http;
use Azmolla\FraudCheckerBdCourier\Helpers\CourierFraudCheckerHelper;

use Azmolla\FraudCheckerBdCourier\Contracts\CourierServiceInterface;

readonly class PathaoService implements CourierServiceInterface
{
    protected string $username;
    protected string $password;

    public function __construct()
    {
        CourierFraudCheckerHelper::checkRequiredConfig([
            'fraud-checker-bd-courier.pathao.user',
            'fraud-checker-bd-courier.pathao.password',
        ]);

        $this->username = config('fraud-checker-bd-courier.pathao.user');
        $this->password = config('fraud-checker-bd-courier.pathao.password');
    }

    public function getDeliveryStats(string $phoneNumber): array
    {
        CourierFraudCheckerHelper::validatePhoneNumber($phoneNumber);

        $response = Http::post('https://merchant.pathao.com/api/v1/login', [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if (!$response->successful()) {
            return ['error' => 'Failed to authenticate with Pathao'];
        }

        $data = $response->json();
        $accessToken = trim($data['access_token'] ?? '');

        if (!$accessToken) {
            return ['error' => 'No access token received from Pathao'];
        }

        $responseAuth = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post('https://merchant.pathao.com/api/v1/user/success', [
            'phone' => $phoneNumber,
        ]);

        if (!$responseAuth->successful()) {
            return ['error' => 'Failed to retrieve customer data', 'status' => $responseAuth->status()];
        }

        $object = $responseAuth->json();

        $success = (int)($object['data']['customer']['successful_delivery'] ?? 0);
        $total = (int)($object['data']['customer']['total_delivery'] ?? 0);
        $cancel = max(0, $total - $success);
        $success_ratio = $total > 0 ? round(($success / $total) * 100, 2) : 0;

        return [
            'success' => $success,
            'cancel' => $cancel,
            'total' => $total,
            'success_ratio' => $success_ratio,
        ];
    }
}

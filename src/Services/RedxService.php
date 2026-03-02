<?php

namespace Azmolla\FraudCheckerBdCourier\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Azmolla\FraudCheckerBdCourier\Helpers\CourierFraudCheckerHelper;

readonly class RedxService
{
    protected string $cacheKey;
    protected int $cacheMinutes;
    protected string $phone;
    protected string $password;

    public function __construct()
    {
        $this->cacheKey = 'redx_access_token';
        $this->cacheMinutes = 50;

        // Validate config presence
        CourierFraudCheckerHelper::checkRequiredConfig([
            'fraud-checker-bd-courier.redx.phone',
            'fraud-checker-bd-courier.redx.password',
        ]);

        // Load from config
        $this->phone = config('fraud-checker-bd-courier.redx.phone');
        $this->password = config('fraud-checker-bd-courier.redx.password');

        CourierFraudCheckerHelper::validatePhoneNumber($this->phone);
    }

    protected function getAccessToken(): ?string
    {
        // Use cached token if available
        $token = Cache::get($this->cacheKey);
        if ($token) {
            return $token;
        }

        // Request new token from RedX
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Accept' => 'application/json, text/plain, */*',
        ])->post('https://api.redx.com.bd/v4/auth/login', [
            'phone' => '88' . $this->phone,
            'password' => $this->password,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $token = $response->json('data.accessToken');
        if ($token) {
            Cache::put($this->cacheKey, $token, now()->addMinutes($this->cacheMinutes));
        }

        return $token;
    }

    public function getCustomerDeliveryStats(string $queryPhone): array
    {
        CourierFraudCheckerHelper::validatePhoneNumber($queryPhone);

        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return ['error' => 'Login failed or unable to get access token'];
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get("https://redx.com.bd/api/redx_se/admin/parcel/customer-success-return-rate?phoneNumber=88{$queryPhone}");

        if ($response->successful()) {
            $object = $response->json();

            return [
                'success' => (int)($object['data']['deliveredParcels'] ?? 0),
                'cancel' => isset($object['data']['totalParcels'], $object['data']['deliveredParcels'])
                    ? ((int)$object['data']['totalParcels'] - (int)$object['data']['deliveredParcels'])
                    : 0,
                'total' => (int)($object['data']['totalParcels'] ?? 0),
            ];
        } elseif ($response->status() === 401) {
            Cache::forget($this->cacheKey);
            return ['error' => 'Access token expired or invalid. Please retry.', 'status' => 401];
        }

        return [
            'success' => 'Threshold hit, wait a minute',
            'cancel' => 'Threshold hit, wait a minute',
            'total' => 'Threshold hit, wait a minute',
        ];
    }
}

<?php

namespace Azmolla\FraudCheckerBdCourier\Services;

use Illuminate\Support\Facades\Http;
use Azmolla\FraudCheckerBdCourier\Helpers\CourierFraudCheckerHelper;

use Azmolla\FraudCheckerBdCourier\Contracts\CourierServiceInterface;

readonly class SteadfastService implements CourierServiceInterface
{
    protected string $email;
    protected string $password;

    public function __construct()
    {
        CourierFraudCheckerHelper::checkRequiredConfig([
            'fraud-checker-bd-courier.steadfast.user',
            'fraud-checker-bd-courier.steadfast.password',
        ]);

        $this->email = config('fraud-checker-bd-courier.steadfast.user');
        $this->password = config('fraud-checker-bd-courier.steadfast.password');
    }

    public function getDeliveryStats(string $phoneNumber): array
    {
        try {
            CourierFraudCheckerHelper::validatePhoneNumber($phoneNumber);

            // Step 1: Fetch login page
            $response = Http::get('https://steadfast.com.bd/login');

            // Extract CSRF token
            preg_match('/<input type="hidden" name="_token" value="(.*?)"/', $response->body(), $matches);
            $token = $matches[1] ?? null;

            if (!$token) {
                return ['error' => 'CSRF token not found for Steadfast login'];
            }

            // Convert CookieJar to array
            $rawCookies = $response->cookies();
            $cookiesArray = [];
            foreach ($rawCookies->toArray() as $cookie) {
                $cookiesArray[$cookie['Name']] = $cookie['Value'];
            }

            // Step 2: Log in
            $loginResponse = Http::withCookies($cookiesArray, 'steadfast.com.bd')
                ->asForm()
                ->post('https://steadfast.com.bd/login', [
                    '_token' => $token,
                    'email' => $this->email,
                    'password' => $this->password,
                ]);

            if (!($loginResponse->successful() || $loginResponse->redirect())) {
                return ['error' => 'Login to Steadfast failed', 'status' => $loginResponse->status()];
            }

            // Rebuild cookies after login
            $loginCookiesArray = [];
            foreach ($loginResponse->cookies()->toArray() as $cookie) {
                $loginCookiesArray[$cookie['Name']] = $cookie['Value'];
            }

            // Step 3: Access fraud data
            $authResponse = Http::withCookies($loginCookiesArray, 'steadfast.com.bd')
                ->get("https://steadfast.com.bd/user/frauds/check/{$phoneNumber}");

            if (!$authResponse->successful()) {
                return ['error' => 'Failed to fetch fraud data from Steadfast', 'status' => $authResponse->status()];
            }

            $object = $authResponse->collect()->toArray();

            $success = (int)($object['total_delivered'] ?? 0);
            $cancel = (int)($object['total_cancelled'] ?? 0);
            $total = $success + $cancel;
            $success_ratio = $total > 0 ? round(($success / $total) * 100, 2) : 0;

            $result = [
                'success' => $success,
                'cancel' => $cancel,
                'total'  => $total,
                'success_ratio' => $success_ratio,
            ];

            // Step 4: Logout
            $logoutGET = Http::withCookies($loginCookiesArray, 'steadfast.com.bd')
                ->get('https://steadfast.com.bd/user/frauds/check');

            if ($logoutGET->successful()) {
                $html = $logoutGET->body();

                if (preg_match('/<meta name="csrf-token" content="(.*?)"/', $html, $matches)) {
                    $csrfToken = $matches[1];

                    Http::withCookies($loginCookiesArray, 'steadfast.com.bd')
                        ->asForm()
                        ->post('https://steadfast.com.bd/logout', [
                            '_token' => $csrfToken,
                        ]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'error' => 'An error occurred while processing Steadfast request',
                'message' => $e->getMessage()
            ];
        }
    }
}

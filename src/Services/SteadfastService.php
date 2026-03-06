<?php

namespace Azmolla\FraudCheckerBdCourier\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Azmolla\FraudCheckerBdCourier\Config\FraudCheckerConfig;
use Azmolla\FraudCheckerBdCourier\Helpers\CourierDataValidator;

use Azmolla\FraudCheckerBdCourier\Contracts\CourierServiceInterface;

/**
 * Class SteadfastService
 *
 * Handles API interactions with Steadfast courier to fetch delivery statistics
 * for a given customer phone number via their web interface.
 *
 * @package Azmolla\FraudCheckerBdCourier\Services
 */
readonly class SteadfastService implements CourierServiceInterface
{
    /**
     * @var string The email address for Steadfast authentication.
     */
    protected string $email;

    /**
     * @var string The password for Steadfast authentication.
     */
    protected string $password;

    /**
     * @var Client
     */
    protected Client $httpClient;

    /**
     * SteadfastService constructor.
     *
     * @param FraudCheckerConfig $config
     * @param Client|null        $httpClient
     */
    public function __construct(FraudCheckerConfig $config, ?Client $httpClient = null)
    {
        CourierDataValidator::enforceConfig($config, [
            'steadfast.user',
            'steadfast.password',
        ]);

        $this->email = $config->get('steadfast.user');
        $this->password = $config->get('steadfast.password');
        $this->httpClient = $httpClient ?? new Client(['timeout' => 15.0, 'cookies' => true]);
    }

    /**
     * Fetch delivery statistics from Steadfast for the given phone number.
     *
     * This method handles the full login flow, extracts CSRF tokens, manages
     * cookies, fetches the fraud data, and then gracefully logs out.
     *
     * @param string $phoneNumber The Bangladeshi mobile number to check.
     * @return array Contains 'success', 'cancel', 'total', and 'success_ratio'.
     *               Returns an array with an 'error' key if any step fails.
     */
    public function getDeliveryStats(string $phoneNumber): array
    {
        try {
            CourierDataValidator::checkBdMobile($phoneNumber);

            $cookieJar = new CookieJar();

            // Step 1: Fetch login page
            $response = $this->httpClient->get('https://steadfast.com.bd/login', [
                'cookies' => $cookieJar,
                'http_errors' => false,
            ]);

            // Extract CSRF token
            preg_match('/<input type="hidden" name="_token" value="(.*?)"/', $response->getBody()->getContents(), $matches);
            $token = $matches[1] ?? null;

            if (!$token) {
                return ['error' => 'CSRF token not found for Steadfast login'];
            }

            // Step 2: Log in
            $loginResponse = $this->httpClient->post('https://steadfast.com.bd/login', [
                'cookies' => $cookieJar,
                'form_params' => [
                    '_token' => $token,
                    'email' => $this->email,
                    'password' => $this->password,
                ],
                'http_errors' => false,
                'allow_redirects' => false, // Capture redirect
            ]);

            $status = $loginResponse->getStatusCode();
            if ($status >= 400 && $status !== 302) {
                return ['error' => 'Login to Steadfast failed', 'status' => $status];
            }

            // Step 3: Access fraud data
            $authResponse = $this->httpClient->get("https://steadfast.com.bd/user/frauds/check/{$phoneNumber}", [
                'cookies' => $cookieJar,
                'http_errors' => false,
            ]);

            if ($authResponse->getStatusCode() >= 400) {
                return ['error' => 'Failed to fetch fraud data from Steadfast', 'status' => $authResponse->getStatusCode()];
            }

            $object = json_decode($authResponse->getBody()->getContents(), true);

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
            $logoutGET = $this->httpClient->get('https://steadfast.com.bd/user/frauds/check', [
                'cookies' => $cookieJar,
                'http_errors' => false,
            ]);

            if ($logoutGET->getStatusCode() < 400) {
                $html = $logoutGET->getBody()->getContents();

                if (preg_match('/<meta name="csrf-token" content="(.*?)"/', $html, $matches)) {
                    $csrfToken = $matches[1];

                    $this->httpClient->post('https://steadfast.com.bd/logout', [
                        'cookies' => $cookieJar,
                        'form_params' => [
                            '_token' => $csrfToken,
                        ],
                        'http_errors' => false,
                    ]);
                }
            }

            return $result;
        } catch (GuzzleException | \Exception $e) {
            return [
                'error' => 'An error occurred while processing Steadfast request',
                'message' => $e->getMessage()
            ];
        }
    }
}

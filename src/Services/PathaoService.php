<?php

namespace Azmolla\FraudCheckerBdCourier\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Azmolla\FraudCheckerBdCourier\Config\FraudCheckerConfig;
use Azmolla\FraudCheckerBdCourier\Helpers\CourierDataValidator;

use Azmolla\FraudCheckerBdCourier\Contracts\CourierServiceInterface;

/**
 * Class PathaoService
 *
 * Handles API interactions with Pathao courier to fetch delivery statistics
 * for a specific customer phone number.
 *
 * @package Azmolla\FraudCheckerBdCourier\Services
 */
readonly class PathaoService implements CourierServiceInterface
{
    /**
     * @var string The username for Pathao API authentication.
     */
    protected string $username;

    /**
     * @var string The password for Pathao API authentication.
     */
    protected string $password;

    /**
     * @var Client
     */
    protected Client $httpClient;

    /**
     * PathaoService constructor.
     *
     * @param FraudCheckerConfig $config
     * @param Client|null        $httpClient
     */
    public function __construct(FraudCheckerConfig $config, ?Client $httpClient = null)
    {
        CourierDataValidator::enforceConfig($config, [
            'pathao.user',
            'pathao.password',
        ]);

        $this->username = $config->get('pathao.user');
        $this->password = $config->get('pathao.password');
        $this->httpClient = $httpClient ?? new Client(['timeout' => 15.0]);
    }

    /**
     * Fetch delivery statistics from Pathao for the given phone number.
     *
     * @param string $phoneNumber The Bangladeshi mobile number to check.
     * @return array Contains 'success', 'cancel', 'total', and 'success_ratio'.
     *               In case of an error, returns an array with an 'error' key.
     */
    public function getDeliveryStats(string $phoneNumber): array
    {
        try {
            CourierDataValidator::checkBdMobile($phoneNumber);

            $response = $this->httpClient->post('https://merchant.pathao.com/api/v1/login', [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() >= 400) {
                return ['error' => 'Failed to authenticate with Pathao', 'status' => $response->getStatusCode()];
            }

            $data = json_decode($response->getBody()->getContents(), true);
            $accessToken = trim($data['access_token'] ?? '');

            if (!$accessToken) {
                return ['error' => 'No access token received from Pathao'];
            }

            $responseAuth = $this->httpClient->post('https://merchant.pathao.com/api/v1/user/success', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'json' => [
                    'phone' => $phoneNumber,
                ],
                'http_errors' => false,
            ]);

            if ($responseAuth->getStatusCode() >= 400) {
                return ['error' => 'Failed to retrieve customer data from Pathao', 'status' => $responseAuth->getStatusCode()];
            }

            $object = json_decode($responseAuth->getBody()->getContents(), true);

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
        } catch (GuzzleException | \Exception $e) {
            return [
                'error' => 'An error occurred while processing Pathao request',
                'message' => $e->getMessage()
            ];
        }
    }
}

<?php

namespace Azmolla\FraudCheckerBdCourier\Helpers;

use Azmolla\FraudCheckerBdCourier\Config\FraudCheckerConfig;
use InvalidArgumentException;

/**
 * Class CourierDataValidator
 *
 * Provides helper methods for environment, configuration, and phone number validation.
 * Centralizes common checks used across different courier service classes.
 *
 * @package Azmolla\FraudCheckerBdCourier\Helpers
 */
class CourierDataValidator
{
    /**
     * Verifies that the required config keys are set in the FraudCheckerConfig instance.
     *
     * @param FraudCheckerConfig $config
     * @param array              $requiredKeys
     * @throws InvalidArgumentException
     */
    public static function enforceConfig(FraudCheckerConfig $config, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            $value = $config->get($key);
            if (empty($value)) {
                throw new InvalidArgumentException(sprintf("The configuration key '%s' is required but missing.", $key));
            }
        }
    }

    /**
     * Validates whether the given string is a proper Bangladeshi mobile number.
     *
     * @param string $mobileNumber
     * @throws InvalidArgumentException
     */
    public static function checkBdMobile(string $mobileNumber): void
    {
        if (empty($mobileNumber)) {
            throw new InvalidArgumentException("Phone number cannot be empty.");
        }

        if (!preg_match('/^01[3-9][0-9]{8}$/', $mobileNumber)) {
            throw new InvalidArgumentException('The provided phone number is invalid. Please format it locally (e.g., 01712345678) without +88.');
        }
    }
}

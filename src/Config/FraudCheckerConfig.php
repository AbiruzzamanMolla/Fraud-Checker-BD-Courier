<?php

namespace Azmolla\FraudCheckerBdCourier\Config;


/**
 * Class FraudCheckerConfig
 *
 * Holds the API credentials required to authenticate with various courier services.
 * This removes the strict dependency on Laravel's config() / env() helpers, allowing
 * usage in any PHP environment.
 *
 * @package Azmolla\FraudCheckerBdCourier\Config
 */
class FraudCheckerConfig
{
    /**
     * @var array
     */
    protected array $config;

    /**
     * FraudCheckerConfig constructor.
     *
     * @param array $config Associative array containing courier credentials.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Retrieve a configuration value using dot notation.
     *
     * @param string $key     The dot-notated key (e.g. 'steadfast.user')
     * @param mixed  $default Default value if key is not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $array = $this->config;
        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $array = &$this->config;

        foreach ($keys as $segment) {
            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }
            $array = &$array[$segment];
        }

        $array = $value;
    }

    /**
     * Load config data into the instance.
     *
     * @param array $data
     * @return self
     */
    public function load(array $data): self
    {
        $this->config = array_merge_recursive($this->config, $data);
        return $this;
    }
}

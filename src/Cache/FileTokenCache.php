<?php

namespace Azmolla\FraudCheckerBdCourier\Cache;

/**
 * Class FileTokenCache
 *
 * A lightweight, file-based caching mechanism to store and retrieve API access
 * tokens without depending on Laravel's Cache facade or a full PSR-16 implementation.
 *
 * @package Azmolla\FraudCheckerBdCourier\Cache
 */
class FileTokenCache
{
    /**
     * @var string Directory where cache files are stored.
     */
    protected string $cacheDir;

    /**
     * FileTokenCache constructor.
     *
     * @param string|null $cacheDir Optional custom directory for cache files.
     *                              Defaults to the system's temporary directory.
     */
    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = rtrim($cacheDir ?? sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'fraud_checker_cache';

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * Generate a safe file path for a cache key.
     *
     * @param string $key
     * @return string
     */
    protected function getFilePath(string $key): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . md5($key) . '.cache';
    }

    /**
     * Retrieve an item from the cache.
     *
     * @param string $key
     * @return mixed|null Returns the cached value or null if expired/not found.
     */
    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = @unserialize($content);
        if (!is_array($data) || !isset($data['expires_at'], $data['value'])) {
            return null;
        }

        if (time() >= $data['expires_at']) {
            $this->forget($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     * @return bool
     */
    public function put(string $key, mixed $value, int $minutes): bool
    {
        $file = $this->getFilePath($key);
        $data = [
            'value'      => $value,
            'expires_at' => time() + ($minutes * 60)
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }
}

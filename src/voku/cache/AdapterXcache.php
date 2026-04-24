<?php

declare(strict_types=1);

namespace voku\cache;

/**
 * AdapterXcache: Xcache-adapter
 */
class AdapterXcache implements iAdapter
{
    /**
     * Internal key used to persist the key registry inside Xcache.
     * Not intended for use by application code.
     */
    private const KEYS_REGISTRY_KEY = '__xcache_adapter_keys__';

    /**
     * @var bool
     */
    public $installed = false;

    /**
     * __construct
     */
    public function __construct()
    {
        if (\extension_loaded('xcache') === true) {
            $this->installed = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return \xcache_isset($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        return \xcache_get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function installed(): bool
    {
        return $this->installed;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): bool
    {
        $result = \xcache_unset($key);

        $keys = $this->getKeysRegistry();
        $filtered = \array_values(\array_diff($keys, [$key]));
        if (\count($filtered) !== \count($keys)) {
            $this->saveKeysRegistry($filtered);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * <p>xcache_clear_cache() wipes the entire Xcache variable store, which includes
     * the key registry stored under {@link KEYS_REGISTRY_KEY}. The registry is therefore
     * implicitly empty after this call, consistent with the contract of getAllKeys().</p>
     */
    public function removeAll(): bool
    {
        if (\defined('XC_TYPE_VAR')) {
            $xCacheCount = xcache_count(XC_TYPE_VAR);
            for ($i = 0; $i < $xCacheCount; $i++) {
                \xcache_clear_cache(XC_TYPE_VAR, $i);
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * <p>Returns the list of keys that have been stored through this adapter instance.
     * The registry is maintained inside Xcache under an internal key and is updated
     * on every {@link set()}, {@link setExpired()}, and {@link remove()} call.</p>
     */
    public function getAllKeys(): array
    {
        return $this->getKeysRegistry();
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value): bool
    {
        $result = \xcache_set($key, $value);

        if ($result) {
            $keys = $this->getKeysRegistry();
            if (!\in_array($key, $keys, true)) {
                $keys[] = $key;
                $this->saveKeysRegistry($keys);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setExpired(string $key, $value, int $ttl = 0): bool
    {
        $result = \xcache_set($key, $value, $ttl);

        if ($result) {
            $keys = $this->getKeysRegistry();
            if (!\in_array($key, $keys, true)) {
                $keys[] = $key;
                $this->saveKeysRegistry($keys);
            }
        }

        return $result;
    }

    /**
     * Read the key registry stored inside Xcache.
     *
     * @return string[]
     */
    private function getKeysRegistry(): array
    {
        if (!\xcache_isset(self::KEYS_REGISTRY_KEY)) {
            return [];
        }

        $stored = \xcache_get(self::KEYS_REGISTRY_KEY);
        if (!\is_string($stored)) {
            return [];
        }

        $keys = @\unserialize($stored, ['allowed_classes' => false]);

        return \is_array($keys) ? $keys : [];
    }

    /**
     * Persist the key registry into Xcache (no TTL so it survives as long as the server allows).
     *
     * @param string[] $keys
     *
     * @return void
     */
    private function saveKeysRegistry(array $keys): void
    {
        if (empty($keys)) {
            \xcache_unset(self::KEYS_REGISTRY_KEY);

            return;
        }

        \xcache_set(self::KEYS_REGISTRY_KEY, \serialize(\array_values($keys)));
    }
}

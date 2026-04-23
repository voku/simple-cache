<?php

declare(strict_types=1);

namespace voku\cache;

use Memcache;
use voku\cache\Exception\InvalidArgumentException;

/**
 * AdapterMemcache: Memcache-adapter
 */
class AdapterMemcache implements iAdapter
{
    /**
     * Internal key used to persist the key registry inside Memcache.
     * Not intended for use by application code.
     */
    private const KEYS_REGISTRY_KEY = '__memcache_adapter_keys__';

    /**
     * @var bool
     */
    public $installed = false;

    /**
     * @var Memcache
     */
    private $memcache;

    /**
     * @var bool
     */
    private $compressed = false;

    /**
     * __construct
     *
     * @param Memcache|null $memcache
     */
    public function __construct($memcache = null)
    {
        if ($memcache instanceof Memcache) {
            $this->setMemcache($memcache);
        }
    }

    /**
     * @param Memcache $memcache
     */
    public function setMemcache(Memcache $memcache)
    {
        $this->memcache = $memcache;
        $this->installed = true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return $this->get($key) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        return $this->memcache->get($key);
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
        $result = $this->memcache->delete($key);

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
     * <p>Calling flush() clears the entire Memcache backend, which includes the key
     * registry stored under {@link KEYS_REGISTRY_KEY}. The registry is therefore
     * implicitly empty after this call, consistent with the contract of getAllKeys().</p>
     */
    public function removeAll(): bool
    {
        return $this->memcache->flush();
    }

    /**
     * {@inheritdoc}
     *
     * <p>Returns the list of keys that have been stored through this adapter instance.
     * The registry is maintained inside Memcache under an internal key and is updated
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
        // Make sure we are under the proper limit
        if (\strlen($key) > 250) {
            throw new InvalidArgumentException('The passed cache key is over 250 bytes:' . \print_r($key, true));
        }

        $result = $this->memcache->set($key, $value, $this->getCompressedFlag());

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
        if ($ttl > 2592000) {
            $ttl = 2592000;
        }

        $result = $this->memcache->set($key, $value, $this->getCompressedFlag(), $ttl);

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
     * Get the compressed-flag from MemCache.
     *
     * @return int 2 || 0
     */
    private function getCompressedFlag(): int
    {
        return $this->isCompressed() ? \MEMCACHE_COMPRESSED : 0;
    }

    /**
     * Check if compression from MemCache is active.
     *
     * @return bool
     */
    public function isCompressed(): bool
    {
        return $this->compressed;
    }

    /**
     * Activate the compression from MemCache.
     *
     * @param mixed $value will be converted to bool
     */
    public function setCompressed($value)
    {
        $this->compressed = (bool) $value;
    }

    /**
     * Read the key registry stored inside Memcache.
     *
     * @return string[]
     */
    private function getKeysRegistry(): array
    {
        $stored = $this->memcache->get(self::KEYS_REGISTRY_KEY);
        if ($stored === false || !\is_string($stored)) {
            return [];
        }

        $keys = @\unserialize($stored, ['allowed_classes' => false]);

        return \is_array($keys) ? $keys : [];
    }

    /**
     * Persist the key registry into Memcache (no TTL so it survives as long as the server allows).
     *
     * @param string[] $keys
     *
     * @return void
     */
    private function saveKeysRegistry(array $keys): void
    {
        if (empty($keys)) {
            $this->memcache->delete(self::KEYS_REGISTRY_KEY);

            return;
        }

        $this->memcache->set(self::KEYS_REGISTRY_KEY, \serialize(\array_values($keys)), 0, 0);
    }
}

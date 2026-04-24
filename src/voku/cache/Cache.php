<?php

declare(strict_types=1);

namespace voku\cache;

use voku\cache\Exception\InvalidArgumentException;

/**
 * Cache: global-cache class
 *
 * can use different cache-adapter:
 * - Redis
 * - Memcache / Memcached
 * - APC / APCu
 * - Xcache
 * - Array
 * - File / OpCache
 */
class Cache implements iCache
{
    /**
     * Reserved store key used to persist the keys registry inside the adapter.
     * Users should not store cache items under this key.
     */
    private const KEYS_INDEX_KEY = '__simple_cache_keys_index__';

    /**
     * @var array<string,mixed>
     */
    protected static $STATIC_CACHE = [];

    /**
     * @var array<string,int>
     */
    protected static $STATIC_CACHE_EXPIRE = [];

    /**
     * @var array<string,int>
     */
    protected static $STATIC_CACHE_COUNTER = [];

    /**
     * @var iAdapter|null
     */
    protected $adapter;

    /**
     * @var iSerializer|null
     */
    protected $serializer;

    /**
     * @var array<array-key,mixed>
     */
    protected $unserialize_options = ['allowed_classes' => true];

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @var bool
     */
    protected $isReady = false;

    /**
     * @var bool
     */
    protected $isActive = true;

    /**
     * @var bool
     */
    protected $useCheckForDev;

    /**
     * @var bool
     */
    protected $useCheckForAdminSession;

    /**
     * @var bool
     */
    protected $useCheckForServerIpIsClientIp;

    /**
     * @var string
     */
    protected $disableCacheGetParameter;

    /**
     * @var bool
     */
    protected $isAdminSession;

    /**
     * @var int
     */
    protected $staticCacheHitCounter = 10;

    /**
     * __construct
     *
     * @param iAdapter|null           $adapter
     * @param iSerializer|null        $serializer
     * @param bool                    $checkForUsage                              <p>check for admin-session && check
     *                                                                            for server-ip == client-ip
     *                                                                            && check for dev</p>
     * @param bool                    $cacheEnabled                               <p>false === disable the cache (use
     *                                                                            it
     *                                                                            e.g. for global settings)</p>
     * @param bool                    $isAdminSession                             <p>true === disable cache for this
     *                                                                            user
     *                                                                            (use it e.g. for admin user settings)
     * @param bool                    $useCheckForAdminSession                    <p>use $isAdminSession flag or
     *                                                                            not</p>
     * @param bool                    $useCheckForDev                             <p>use checkForDev() or not</p>
     * @param bool                    $useCheckForServerIpIsClientIp              <p>use check for server-ip ==
     *                                                                            client-ip or not</p>
     * @param string                  $disableCacheGetParameter                   <p>set the _GET parameter for
     *                                                                            disabling the cache, disable this
     *                                                                            check via empty string</p>
     * @param CacheAdapterAutoManager $cacheAdapterManagerForAutoConnect          <p>Overwrite some Adapters for the
     *                                                                            auto-connect-function.</p>
     * @param bool                    $cacheAdapterManagerForAutoConnectOverwrite <p>true === Use only Adapters from
     *                                                                            your
     *                                                                            "CacheAdapterManager".</p>
     */
    public function __construct(
        ?iAdapter $adapter = null,
        ?iSerializer $serializer = null,
        bool $checkForUsage = true,
        bool $cacheEnabled = true,
        bool $isAdminSession = false,
        bool $useCheckForDev = true,
        bool $useCheckForAdminSession = true,
        bool $useCheckForServerIpIsClientIp = true,
        string $disableCacheGetParameter = 'testWithoutCache',
        ?CacheAdapterAutoManager $cacheAdapterManagerForAutoConnect = null,
        bool $cacheAdapterManagerForAutoConnectOverwrite = false
    ) {
        $this->isAdminSession = $isAdminSession;

        $this->useCheckForDev = $useCheckForDev;
        $this->useCheckForAdminSession = $useCheckForAdminSession;
        $this->useCheckForServerIpIsClientIp = $useCheckForServerIpIsClientIp;

        $this->disableCacheGetParameter = $disableCacheGetParameter;

        // First check if the cache is active at all.
        $this->isActive = $cacheEnabled;
        if (
            $this->isActive
            &&
            $checkForUsage
        ) {
            $this->setActive($this->isCacheActiveForTheCurrentUser());
        }

        // If the cache is active, then try to auto-connect to the best possible cache-system.
        if ($this->isActive) {
            $this->setPrefix($this->getTheDefaultPrefix());

            if ($adapter === null) {
                $adapter = $this->autoConnectToAvailableCacheSystem($cacheAdapterManagerForAutoConnect, $cacheAdapterManagerForAutoConnectOverwrite);
            }

            if (!\is_object($serializer) && $serializer === null) {
                if (
                    $adapter instanceof AdapterMemcached
                    ||
                    $adapter instanceof AdapterMemcache
                ) {
                    // INFO: Memcache(d) has his own "serializer", so don't use it twice
                    $serializer = new SerializerNo();
                } elseif (
                    $adapter instanceof AdapterOpCache
                    &&
                    \class_exists('\Symfony\Component\VarExporter\VarExporter')
                ) {
                    // INFO: opcache + Symfony-VarExporter don't need any "serializer"
                    $serializer = new SerializerNo();
                } else {
                    // set default serializer
                    $serializer = new SerializerIgbinary();
                }
            }
        }

        // Final checks ...
        if (
            $serializer !== null
            &&
            $adapter !== null
        ) {
            $this->setCacheIsReady(true);

            $this->adapter = $adapter;

            $this->serializer = $serializer;

            $this->serializer->setUnserializeOptions($this->unserialize_options);
        }
    }

    /**
     * @return iAdapter|null
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return iSerializer|null
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param array<array-key,mixed> $array
     *
     * @return void
     */
    public function setUnserializeOptions(array $array = [])
    {
        $this->unserialize_options = $array;
    }

    /**
     * Auto-connect to the available cache-system on the server.
     *
     * @param CacheAdapterAutoManager $cacheAdapterManagerForAutoConnect          <p>Overwrite some Adapters for the
     *                                                                            auto-connect-function.</p>
     * @param bool                    $cacheAdapterManagerForAutoConnectOverwrite <p>true === Use only Adapters from
     *                                                                            your
     *                                                                            "CacheAdapterManager".</p>
     *
     * @return iAdapter
     */
    protected function autoConnectToAvailableCacheSystem(
        ?CacheAdapterAutoManager $cacheAdapterManagerForAutoConnect = null,
        bool $cacheAdapterManagerForAutoConnectOverwrite = false
    ): iAdapter {
        /** @var null|iAdapter $AUTO_ADAPTER_STATIC_CACHE */
        static $AUTO_ADAPTER_STATIC_CACHE = null;

        if (
            \is_object($AUTO_ADAPTER_STATIC_CACHE)
            &&
            $AUTO_ADAPTER_STATIC_CACHE instanceof iAdapter
        ) {
            return $AUTO_ADAPTER_STATIC_CACHE;
        }

        // init
        $adapter = null;

        $cacheAdapterManagerDefault = CacheAdapterAutoManager::getDefaultsForAutoInit();

        if ($cacheAdapterManagerForAutoConnect !== null) {
            if ($cacheAdapterManagerForAutoConnectOverwrite) {
                $cacheAdapterManagerDefault = $cacheAdapterManagerForAutoConnect;
            } else {
                /** @noinspection PhpUnhandledExceptionInspection */
                $cacheAdapterManagerDefault->merge($cacheAdapterManagerForAutoConnect);
            }
        }

        foreach ($cacheAdapterManagerDefault->getAdapters() as $adapterTmp => $callableFunctionTmp) {
            if ($callableFunctionTmp !== null) {
                $adapterTest = new $adapterTmp($callableFunctionTmp);
            } else {
                $adapterTest = new $adapterTmp();
            }
            assert($adapterTest instanceof iAdapter);

            if ($adapterTest->installed()) {
                $adapter = $adapterTest;

                break;
            }
        }

        assert($adapter instanceof iAdapter);

        // save to static cache
        $AUTO_ADAPTER_STATIC_CACHE = $adapter;

        return $adapter;
    }

    /**
     * Calculate store-key (prefix + $rawKey).
     *
     * @param string $rawKey
     *
     * @return string
     */
    protected function calculateStoreKey(string $rawKey): string
    {
        $str = $this->getPrefix() . $rawKey;

        if ($this->adapter instanceof AdapterFileAbstract) {
            $str = $this->cleanStoreKey($str);
        }

        return $str;
    }

    /**
     * Check for local developer.
     *
     * @return bool
     */
    protected function checkForDev(): bool
    {
        $return = false;

        if (\function_exists('checkForDev')) {
            $return = checkForDev();
        } else {
            // for testing with dev-address
            $noDev = isset($_GET['noDev']) ? (int) $_GET['noDev'] : 0;
            $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'NO_REMOTE_ADDR';

            if (
                $noDev !== 1
                &&
                (
                    $remoteAddr === '127.0.0.1'
                    ||
                    $remoteAddr === '::1'
                    ||
                    \PHP_SAPI === 'cli'
                )
            ) {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * @param string $storeKey
     *
     * @return bool
     */
    protected function checkForStaticCache(string $storeKey): bool
    {
        return !empty(self::$STATIC_CACHE)
               &&
               \array_key_exists($storeKey, self::$STATIC_CACHE)
               &&
               \array_key_exists($storeKey, self::$STATIC_CACHE_EXPIRE)
               &&
               \time() <= self::$STATIC_CACHE_EXPIRE[$storeKey];
    }

    /**
     * Clean store-key (required e.g. for the "File"-Adapter).
     *
     * @param string $str
     *
     * @return string
     */
    protected function cleanStoreKey(string $str): string
    {
        return \md5($str);
    }

    /**
     * Get the store key used to persist the keys registry in the adapter.
     *
     * @return string
     */
    private function getKeysIndexStoreKey(): string
    {
        return $this->calculateStoreKey(self::KEYS_INDEX_KEY);
    }

    /**
     * Read all tracked raw (unprefixed) keys from the keys registry.
     *
     * @return string[]
     */
    private function getKeysFromIndex(): array
    {
        if (!$this->adapter instanceof iAdapter) {
            return [];
        }

        $stored = $this->adapter->get($this->getKeysIndexStoreKey());
        if ($stored === null || !\is_string($stored)) {
            return [];
        }

        // Use PHP's native unserialize so the registry format is independent of
        // whatever $this->serializer is configured to use.  This prevents
        // cross-serializer corruption when a shared backend (static AdapterArray,
        // APCu) is accessed by different Cache instances that use different
        // serializers (e.g. SerializerDefault in ArrayCacheTest vs
        // SerializerIgbinary in CacheChainTest).
        $keys = @\unserialize($stored, ['allowed_classes' => false]);

        return \is_array($keys) ? $keys : [];
    }

    /**
     * Persist a list of raw keys to the keys registry in the adapter.
     *
     * @param string[] $keys
     *
     * @return void
     */
    private function saveKeysToIndex(array $keys): void
    {
        if (!$this->adapter instanceof iAdapter) {
            return;
        }

        $indexKey = $this->getKeysIndexStoreKey();

        if (empty($keys)) {
            $this->adapter->remove($indexKey);

            return;
        }

        // Always use PHP's native serialize so the registry format is independent
        // of $this->serializer and can be safely read by any Cache instance that
        // targets the same backend, regardless of its configured serializer.
        $this->adapter->set($indexKey, \serialize(\array_values($keys)));
    }

    /**
     * Add a raw key to the keys registry (no-op if already present).
     *
     * @param string $key
     *
     * @return void
     */
    private function addKeyToIndex(string $key): void
    {
        $keys = $this->getKeysFromIndex();
        if (!\in_array($key, $keys, true)) {
            $keys[] = $key;
            $this->saveKeysToIndex($keys);
        }
    }

    /**
     * Remove a raw key from the keys registry.
     *
     * @param string $key
     *
     * @return void
     */
    private function removeKeyFromIndex(string $key): void
    {
        $keys = $this->getKeysFromIndex();
        $filtered = \array_values(\array_diff($keys, [$key]));
        if (\count($filtered) !== \count($keys)) {
            $this->saveKeysToIndex($filtered);
        }
    }

    /**
     * Check if cached-item exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function existsItem(string $key): bool
    {
        if (!$this->adapter instanceof iAdapter) {
            return false;
        }

        $storeKey = $this->calculateStoreKey($key);

        // check static-cache
        if ($this->checkForStaticCache($storeKey)) {
            return true;
        }

        return $this->adapter->exists($storeKey);
    }

    /**
     * Get cached-item by key.
     *
     * @param string $key
     * @param int    $forceStaticCacheHitCounter
     *
     * @return mixed
     */
    public function getItem(string $key, int $forceStaticCacheHitCounter = 0)
    {
        if (!$this->adapter instanceof iAdapter) {
            return null;
        }

        $storeKey = $this->calculateStoreKey($key);

        // check if we already using static-cache
        $useStaticCache = true;
        if ($this->adapter instanceof AdapterArray) {
            $useStaticCache = false;
        }

        if (!isset(self::$STATIC_CACHE_COUNTER[$storeKey])) {
            self::$STATIC_CACHE_COUNTER[$storeKey] = 0;
        }

        // get from static-cache
        if (
            $useStaticCache
            &&
            $this->checkForStaticCache($storeKey)
        ) {
            return self::$STATIC_CACHE[$storeKey];
        }

        $serialized = $this->adapter->get($storeKey);
        if ($this->serializer && $this->serializer instanceof SerializerNo) {
            $value = $serialized;
        } else {
            $value = $serialized && $this->serializer ? $this->serializer->unserialize($serialized) : null;
        }

        self::$STATIC_CACHE_COUNTER[$storeKey]++;

        // save into static-cache if needed
        if (
            $useStaticCache
            &&
            (
                (
                    $forceStaticCacheHitCounter !== 0
                    &&
                    self::$STATIC_CACHE_COUNTER[$storeKey] >= $forceStaticCacheHitCounter
                )
                ||
                (
                    $this->staticCacheHitCounter !== 0
                    &&
                    self::$STATIC_CACHE_COUNTER[$storeKey] >= $this->staticCacheHitCounter
                )
            )
        ) {
            self::$STATIC_CACHE[$storeKey] = $value;
        }

        return $value;
    }

    /**
     * Remove all cached-items.
     *
     * @return bool
     */
    public function removeAll(): bool
    {
        if (!$this->adapter instanceof iAdapter) {
            return false;
        }

        // remove static-cache
        if (!empty(self::$STATIC_CACHE)) {
            self::$STATIC_CACHE = [];
            self::$STATIC_CACHE_COUNTER = [];
            self::$STATIC_CACHE_EXPIRE = [];
        }

        return $this->adapter->removeAll();
    }

    /**
     * Remove a cached-item.
     *
     * @param string $key
     *
     * @return bool
     */
    public function removeItem(string $key): bool
    {
        if (!$this->adapter instanceof iAdapter) {
            return false;
        }

        $storeKey = $this->calculateStoreKey($key);

        // remove static-cache
        if (
            !empty(self::$STATIC_CACHE)
            &&
            \array_key_exists($storeKey, self::$STATIC_CACHE)
        ) {
            unset(
                self::$STATIC_CACHE[$storeKey],
                self::$STATIC_CACHE_COUNTER[$storeKey],
                self::$STATIC_CACHE_EXPIRE[$storeKey]
            );
        }

        $result = $this->adapter->remove($storeKey);

        // Always clean the registry, even when the item was already gone (e.g. expired).
        $this->removeKeyFromIndex($key);

        return $result;
    }

    /**
     * Remove all cached-items whose keys match a given regular expression.
     *
     * <p>The pattern is matched against the raw (unprefixed) key supplied to setItem().
     * This method works with all adapters because it uses an in-adapter key registry
     * maintained by setItem() / removeItem() / removeAll().</p>
     *
     * @param string $pattern A valid PHP regular expression (e.g. '/^imagecache_/').
     *
     * @return bool
     *              <p>Returns true on success or when no tracked keys match the pattern.
     *              Returns false only if a matched item that still exists could not be removed.</p>
     */
    public function removeItems(string $pattern): bool
    {
        if (!$this->adapter instanceof iAdapter) {
            return false;
        }

        $rawKeys = $this->getKeysFromIndex();
        if (empty($rawKeys)) {
            return true;
        }

        $results = [];
        $keysToRemove = [];

        foreach ($rawKeys as $rawKey) {
            if (\preg_match($pattern, $rawKey) !== 1) {
                continue;
            }

            $storeKey = $this->calculateStoreKey($rawKey);

            // Remove from static-cache
            if (
                !empty(self::$STATIC_CACHE)
                &&
                \array_key_exists($storeKey, self::$STATIC_CACHE)
            ) {
                unset(
                    self::$STATIC_CACHE[$storeKey],
                    self::$STATIC_CACHE_COUNTER[$storeKey],
                    self::$STATIC_CACHE_EXPIRE[$storeKey]
                );
            }

            $removed = $this->adapter->remove($storeKey);
            // Treat an already-absent item (e.g. expired) as successfully removed.
            if (!$removed && !$this->adapter->exists($storeKey)) {
                $removed = true;
            }
            $results[] = $removed;
            $keysToRemove[] = $rawKey;
        }

        // Prune matched keys from the registry regardless of removal outcome.
        if (!empty($keysToRemove)) {
            $remainingKeys = \array_values(\array_diff($rawKeys, $keysToRemove));
            $this->saveKeysToIndex($remainingKeys);
        }

        return \in_array(false, $results, true) === false;
    }

    /**
     * Set cache-item by key => value + ttl.
     *
     * @param string                 $key
     * @param mixed                  $value
     * @param \DateInterval|int|null $ttl
     *
     * @throws \InvalidArgumentException
     *
     * @return bool
     */
    public function setItem(string $key, $value, $ttl = 0): bool
    {
        if (
            !$this->adapter instanceof iAdapter
            ||
            !$this->serializer instanceof iSerializer
        ) {
            return false;
        }

        $storeKey = $this->calculateStoreKey($key);
        $serialized = $this->serializer->serialize($value);

        // update static-cache, if it's exists
        if (\array_key_exists($storeKey, self::$STATIC_CACHE)) {
            self::$STATIC_CACHE[$storeKey] = $value;
        }

        if ($ttl) {
            if ($ttl instanceof \DateInterval) {
                // Converting to a TTL in seconds
                $ttl = (new \DateTimeImmutable('now'))->add($ttl)->getTimestamp() - \time();
            }

            // always cache the TTL time, maybe we need this later ...
            self::$STATIC_CACHE_EXPIRE[$storeKey] = ($ttl ? (int) $ttl + \time() : 0);

            $result = $this->adapter->setExpired($storeKey, $serialized, $ttl);
        } else {
            $result = $this->adapter->set($storeKey, $serialized);
        }

        if ($result) {
            $this->addKeyToIndex($key);
        }

        return $result;
    }

    /**
     * Set cache-item by key => value + date.
     *
     * @param string             $key
     * @param mixed              $value
     * @param \DateTimeInterface $date <p>If the date is in the past, we will remove the existing cache-item.</p>
     *
     * @throws InvalidArgumentException
     *                                   <p>If the $date is in the past.</p>
     *
     * @return bool
     */
    public function setItemToDate(string $key, $value, \DateTimeInterface $date): bool
    {
        $ttl = $date->getTimestamp() - \time();

        if ($ttl <= 0) {
            throw new InvalidArgumentException('Date in the past.');
        }

        return $this->setItem($key, $value, $ttl);
    }

    /**
     * Get the "isReady" state.
     *
     * @return bool
     */
    public function getCacheIsReady(): bool
    {
        return $this->isReady;
    }

    /**
     * returns the IP address of the client
     *
     * @param bool $trust_proxy_headers     <p>
     *                                      Whether or not to trust the
     *                                      proxy headers HTTP_CLIENT_IP
     *                                      and HTTP_X_FORWARDED_FOR. ONLY
     *                                      use if your $_SERVER is behind a
     *                                      proxy that sets these values
     *                                      </p>
     *
     * @return string
     */
    protected function getClientIp(bool $trust_proxy_headers = false): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'NO_REMOTE_ADDR';

        if ($trust_proxy_headers) {
            return $remoteAddr;
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $remoteAddr;
        }

        return $ip;
    }

    /**
     * Get the prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get the current value, when the static cache is used.
     *
     * @return int
     */
    public function getStaticCacheHitCounter(): int
    {
        return $this->staticCacheHitCounter;
    }

    /**
     * Set the default-prefix via "SERVER"-var + "SESSION"-language.
     *
     * @return string
     */
    protected function getTheDefaultPrefix(): string
    {
        return ($_SERVER['SERVER_NAME'] ?? '') . '_' .
               ($_SERVER['THEME'] ?? '') . '_' .
               ($_SERVER['STAGE'] ?? '') . '_' .
               ($_SESSION['language'] ?? '') . '_' .
               ($_SESSION['language_extra'] ?? '') . '_' .
               \PHP_VERSION_ID . '_' .
               ($this->serializer ? $this->serializer->getName() : '');
    }

    /**
     * Get the current adapter class-name.
     *
     * @return string
     *
     * @psalm-return class-string|string
     */
    public function getUsedAdapterClassName(): string
    {
        if ($this->adapter) {
            return \get_class($this->adapter);
        }

        return '';
    }

    /**
     * Get the current serializer class-name.
     *
     * @return string
     *
     * @psalm-return class-string|string
     */
    public function getUsedSerializerClassName(): string
    {
        if ($this->serializer) {
            return \get_class($this->serializer);
        }

        return '';
    }

    /**
     * check if the current use is a admin || dev || server == client
     *
     * @return bool
     */
    public function isCacheActiveForTheCurrentUser(): bool
    {
        // init
        $active = true;

        // test the cache, with this GET-parameter
        if ($this->disableCacheGetParameter) {
            $testCache = isset($_GET[$this->disableCacheGetParameter]) ? (int) $_GET[$this->disableCacheGetParameter] : 0;
        } else {
            $testCache = 0;
        }

        if ($testCache !== 1) {
            if (
                // admin session is active
                (
                    $this->useCheckForAdminSession
                    &&
                    $this->isAdminSession
                )
                ||
                // server == client
                (
                    $this->useCheckForServerIpIsClientIp
                    &&
                    isset($_SERVER['SERVER_ADDR'])
                    &&
                    $_SERVER['SERVER_ADDR'] === $this->getClientIp()
                )
                ||
                // user is a dev
                (
                    $this->useCheckForDev
                    &&
                    $this->checkForDev()
                )
            ) {
                $active = false;
            }
        }

        return $active;
    }

    /**
     * enable / disable the cache
     *
     * @param bool $isActive
     *
     * @return void
     */
    public function setActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * Set "isReady" state.
     *
     * @param bool $isReady
     *
     * @return void
     */
    protected function setCacheIsReady(bool $isReady)
    {
        $this->isReady = $isReady;
    }

    /**
     * !!! Set the prefix. !!!
     *
     * WARNING: Do not use if you don't know what you do. Because this will overwrite the default prefix.
     *
     * @param string $prefix
     *
     * @return void
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Set the static-hit-counter: Who often do we hit the cache, before we use static cache?
     *
     * @param int $staticCacheHitCounter
     *
     * @return void
     */
    public function setStaticCacheHitCounter(int $staticCacheHitCounter)
    {
        $this->staticCacheHitCounter = $staticCacheHitCounter;
    }
}

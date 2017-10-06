<?php

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
 * - File
 *
 * @package   voku\cache
 */
class Cache implements iCache
{

  /**
   * @var iAdapter
   */
  protected $adapter;

  /**
   * @var iSerializer
   */
  protected $serializer;

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
   * @var mixed no cache, if admin-session is set
   */
  protected $isAdminSession = false;

  /**
   * @var array
   */
  protected static $STATIC_CACHE = array();

  /**
   * @var array
   */
  protected static $STATIC_CACHE_EXPIRE = array();

  /**
   * @var array
   */
  protected static $STATIC_CACHE_COUNTER = array();

  /**
   * @var int
   */
  protected $staticCacheHitCounter = 10;

  /**
   * __construct
   *
   * @param null|iAdapter    $adapter
   * @param null|iSerializer $serializer
   * @param boolean          $checkForUser   check for dev-ip or if cms-user is logged-in
   * @param boolean          $cacheEnabled   false will disable the cache (use it e.g. for global settings)
   * @param string|boolean   $isAdminSession set a user-id, if the user is a admin (so we can disable cache for this
   *                                         user)
   */
  public function __construct(iAdapter $adapter = null, iSerializer $serializer = null, $checkForUser = true, $cacheEnabled = true, $isAdminSession = false)
  {
    $this->isAdminSession = $isAdminSession;

    // First check if the cache is active at all.
    $this->setActive($cacheEnabled);
    if (
        $this->isActive === true
        &&
        $checkForUser === true
    ) {
      $this->setActive($this->isCacheActiveForTheCurrentUser());
    }

    // If the cache is active, then try to auto-connect to the best possible cache-system.
    if ($this->isActive === true) {

      $this->setPrefix($this->getTheDefaultPrefix());

      if (
          $adapter === null
          ||
          !is_object($adapter)
          ||
          !$adapter instanceof iAdapter
      ) {
        $adapter = $this->autoConnectToAvailableCacheSystem();
      }

      // INFO: Memcache(d) has his own "serializer", so don't use it twice
      if (!is_object($serializer) && $serializer === null) {
        if (
            $adapter instanceof AdapterMemcached
            ||
            $adapter instanceof AdapterMemcache
        ) {
          $serializer = new SerializerNo();
        } else {
          // set default serializer
          $serializer = new SerializerIgbinary();
        }
      }
    }

    // Final checks ...
    if (
        $serializer instanceof iSerializer
        &&
        $adapter instanceof iAdapter
    ) {
      $this->setCacheIsReady(true);

      $this->adapter = $adapter;
      $this->serializer = $serializer;
    }
  }

  /**
   * enable / disable the cache
   *
   * @param boolean $isActive
   */
  public function setActive($isActive)
  {
    $this->isActive = (boolean)$isActive;
  }

  /**
   * check if the current use is a admin || dev || server == client
   *
   * @return bool
   */
  public function isCacheActiveForTheCurrentUser()
  {
    $active = true;

    // test the cache, with this GET-parameter
    $testCache = isset($_GET['testCache']) ? (int)$_GET['testCache'] : 0;

    if ($testCache != 1) {
      if (
          // admin is logged-in
          $this->isAdminSession
          ||
          // server == client
          (
              isset($_SERVER['SERVER_ADDR'])
              &&
              $_SERVER['SERVER_ADDR'] == $this->getClientIp()
          )
          ||
          // user is a dev
          $this->checkForDev() === true
      ) {
        $active = false;
      }
    }

    return $active;
  }

  /**
   * returns the IP address of the client
   *
   * @param   bool $trust_proxy_headers   Whether or not to trust the
   *                                      proxy headers HTTP_CLIENT_IP
   *                                      and HTTP_X_FORWARDED_FOR. ONLY
   *                                      use if your $_SERVER is behind a
   *                                      proxy that sets these values
   *
   * @return  string
   */
  protected function getClientIp($trust_proxy_headers = false)
  {
    $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'NO_REMOTE_ADDR';

    if ($trust_proxy_headers) {
      return $remoteAddr;
    }

    if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $remoteAddr;
    }

    return $ip;
  }

  /**
   * Check for local developer.
   *
   * @return bool
   */
  protected function checkForDev()
  {
    $return = false;

    if (function_exists('checkForDev')) {
      $return = checkForDev();
    } else {

      // for testing with dev-address
      $noDev = isset($_GET['noDev']) ? (int)$_GET['noDev'] : 0;
      $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'NO_REMOTE_ADDR';

      if (
          $noDev != 1
          &&
          (
              $remoteAddr === '127.0.0.1'
              ||
              $remoteAddr === '::1'
              ||
              PHP_SAPI === 'cli'
          )
      ) {
        $return = true;
      }
    }

    return $return;
  }

  /**
   * Set the default-prefix via "SERVER"-var + "SESSION"-language.
   */
  protected function getTheDefaultPrefix()
  {
    return (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '') . '_' .
           (isset($_SERVER['THEME']) ? $_SERVER['THEME'] : '') . '_' .
           (isset($_SERVER['STAGE']) ? $_SERVER['STAGE'] : '') . '_' .
           (isset($_SESSION['language']) ? $_SESSION['language'] : '') . '_' .
           (isset($_SESSION['language_extra']) ? $_SESSION['language_extra'] : '');
  }

  /**
   * Auto-connect to the available cache-system on the server.
   *
   * @return iAdapter
   */
  protected function autoConnectToAvailableCacheSystem()
  {
    static $adapterCache;

    if (is_object($adapterCache) && $adapterCache instanceof iAdapter) {
      return $adapterCache;
    }

    $memcached = null;
    $isMemcachedAvailable = false;
    if (extension_loaded('memcached')) {
      $memcached = new \Memcached();
      /** @noinspection PhpUsageOfSilenceOperatorInspection */
      $isMemcachedAvailable = @$memcached->addServer('127.0.0.1', 11211);
    }

    if ($isMemcachedAvailable === false) {
      $memcached = null;
    }

    $adapterMemcached = new AdapterMemcached($memcached);
    if ($adapterMemcached->installed() === true) {

      // -------------------------------------------------------------
      // "Memcached"
      // -------------------------------------------------------------
      $adapter = $adapterMemcached;

    } else {

      $memcache = null;
      $isMemcacheAvailable = false;
      if (class_exists('\Memcache')) {
        $memcache = new \Memcache;
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $isMemcacheAvailable = @$memcache->connect('127.0.0.1', 11211);
      }

      if ($isMemcacheAvailable === false) {
        $memcache = null;
      }

      $adapterMemcache = new AdapterMemcache($memcache);
      if ($adapterMemcache->installed() === true) {

        // -------------------------------------------------------------
        // "Memcache"
        // -------------------------------------------------------------
        $adapter = $adapterMemcache;

      } else {

        $redis = null;
        $isRedisAvailable = false;
        if (
            extension_loaded('redis')
            &&
            class_exists('\Predis\Client')
        ) {
          /** @noinspection PhpUndefinedNamespaceInspection */
          /** @noinspection PhpUndefinedClassInspection */
          $redis = new \Predis\Client(
              array(
                  'scheme'  => 'tcp',
                  'host'    => '127.0.0.1',
                  'port'    => 6379,
                  'timeout' => '2.0',
              )
          );
          try {
            $redis->connect();
            $isRedisAvailable = $redis->getConnection()->isConnected();
          } catch (\Exception $e) {
            // nothing
          }
        }

        if ($isRedisAvailable === false) {
          $redis = null;
        }

        $adapterRedis = new AdapterPredis($redis);
        if ($adapterRedis->installed() === true) {

          // -------------------------------------------------------------
          // Redis
          // -------------------------------------------------------------
          $adapter = $adapterRedis;

        } else {

          $adapterXcache = new AdapterXcache();
          if ($adapterXcache->installed() === true) {

            // -------------------------------------------------------------
            // "Xcache"
            // -------------------------------------------------------------
            $adapter = $adapterXcache;

          } else {

            $adapterApc = new AdapterApc();
            if ($adapterApc->installed() === true) {

              // -------------------------------------------------------------
              // "APC"
              // -------------------------------------------------------------
              $adapter = $adapterApc;

            } else {

              $adapterApcu = new AdapterApcu();
              if ($adapterApcu->installed() === true) {

                // -------------------------------------------------------------
                // "APCu"
                // -------------------------------------------------------------
                $adapter = $adapterApcu;

              } else {

                $adapterFile = new AdapterFile();
                if ($adapterFile->installed() === true) {

                  // -------------------------------------------------------------
                  // File-Cache
                  // -------------------------------------------------------------
                  $adapter = $adapterFile;

                } else {

                  // -------------------------------------------------------------
                  // Static-PHP-Cache
                  // -------------------------------------------------------------
                  $adapter = new AdapterArray();
                }
              }
            }
          }
        }
      }
    }

    // save to static cache
    $adapterCache = $adapter;

    return $adapter;
  }

  /**
   * Set "isReady" state.
   *
   * @param boolean $isReady
   */
  protected function setCacheIsReady($isReady)
  {
    $this->isReady = (boolean)$isReady;
  }

  /**
   * Get the "isReady" state.
   *
   * @return boolean
   */
  public function getCacheIsReady()
  {
    return $this->isReady;
  }

  /**
   * Get cached-item by key.
   *
   * @param string $key
   * @param int    $forceStaticCacheHitCounter
   *
   * @return mixed
   */
  public function getItem($key, $forceStaticCacheHitCounter = 0)
  {
    // init
    $forceStaticCacheHitCounter = (int)$forceStaticCacheHitCounter;

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
        $useStaticCache === true
        &&
        $this->checkForStaticCache($storeKey) === true
    ) {
      return self::$STATIC_CACHE[$storeKey];
    }

    $serialized = $this->adapter->get($storeKey);
    $value = $serialized ? $this->serializer->unserialize($serialized) : null;

    self::$STATIC_CACHE_COUNTER[$storeKey]++;

    // save into static-cache if needed
    if (
        $useStaticCache === true
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
   * Calculate store-key (prefix + $rawKey).
   *
   * @param string $rawKey
   *
   * @return string
   */
  protected function calculateStoreKey($rawKey)
  {
    $str = $this->getPrefix() . $rawKey;

    if ($this->adapter instanceof AdapterFile) {
      $str = $this->cleanStoreKey($str);
    }

    return $str;
  }

  /**
   * Clean store-key (required e.g. for the "File"-Adapter).
   *
   * @param string $str
   *
   * @return string
   */
  protected function cleanStoreKey($str)
  {
    $str = preg_replace("/[\r\n\t ]+/", ' ', $str);
    $str = str_replace(
        array('"', '*', ':', '<', '>', '?', "'", '|'),
        array(
            '-+-',
            '-+-+-',
            '-+-+-+-',
            '-+-+-+-+-',
            '-+-+-+-+-+-',
            '-+-+-+-+-+-+-',
            '-+-+-+-+-+-+-+-',
            '-+-+-+-+-+-+-+-+-',
        ),
        $str
    );
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    $str = htmlentities($str, ENT_QUOTES, 'UTF-8');
    $str = preg_replace('/(&)([a-z])([a-z]+;)/i', '$2', $str);
    $str = str_replace(' ', '-', $str);
    $str = rawurlencode($str);
    $str = str_replace('%', '-', $str);

    return $str;
  }

  /**
   * Get the prefix.
   *
   * @return string
   */
  public function getPrefix()
  {
    return $this->prefix;
  }

  /**
   * !!! Set the prefix. !!!
   *
   * WARNING: Do not use if you don't know what you do. Because this will overwrite the default prefix.
   *
   * @param string $prefix
   */
  public function setPrefix($prefix)
  {
    $this->prefix = (string)$prefix;
  }

  /**
   * Get the current value, when the static cache is used.
   *
   * @return int
   */
  public function getStaticCacheHitCounter()
  {
    return $this->staticCacheHitCounter;
  }

  /**
   * Set the static-hit-counter: Who often do we hit the cache, before we use static cache?
   *
   * @param int $staticCacheHitCounter
   */
  public function setStaticCacheHitCounter($staticCacheHitCounter)
  {
    $this->staticCacheHitCounter = (int)$staticCacheHitCounter;
  }

  /**
   * Set cache-item by key => value + date.
   *
   * @param string    $key
   * @param mixed     $value
   * @param \DateTime $date <p>If the date is in the past, we will remove the existing cache-item.</p>
   *
   * @return boolean
   * @throws \Exception
   */
  public function setItemToDate($key, $value, \DateTime $date)
  {
    $ttl = $date->getTimestamp() - time();

    if ($ttl <= 0) {
      throw new InvalidArgumentException('Date in the past.');
    }

    $storeKey = $this->calculateStoreKey($key);

    return $this->setItem($storeKey, $value, $ttl);
  }

  /**
   * Set cache-item by key => value + ttl.
   *
   * @param string $key
   * @param mixed  $value
   * @param null|int|\DateInterval $ttl
   *
   * @return bool
   */
  public function setItem($key, $value, $ttl = 0)
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
    if (array_key_exists($storeKey, self::$STATIC_CACHE) === true) {
      self::$STATIC_CACHE[$storeKey] = $value;
    }

    if ($ttl) {

      if ($ttl instanceof \DateInterval) {
        // Converting to a TTL in seconds
        $dateTimeNow = new \DateTime('now');
        $ttl = $dateTimeNow->add($ttl)->getTimestamp() - time();
      }

      // always cache the TTL time, maybe we need this later ...
      self::$STATIC_CACHE_EXPIRE[$storeKey] = ($ttl ? (int)$ttl + time() : 0);

      return $this->adapter->setExpired($storeKey, $serialized, $ttl);
    }

    return $this->adapter->set($storeKey, $serialized);
  }

  /**
   * Remove a cached-item.
   *
   * @param string $key
   *
   * @return bool
   */
  public function removeItem($key)
  {
    if (!$this->adapter instanceof iAdapter) {
      return false;
    }

    $storeKey = $this->calculateStoreKey($key);

    // remove static-cache
    if (
        !empty(self::$STATIC_CACHE)
        &&
        array_key_exists($storeKey, self::$STATIC_CACHE) === true
    ) {
      unset(
          self::$STATIC_CACHE[$storeKey],
          self::$STATIC_CACHE_COUNTER[$storeKey],
          self::$STATIC_CACHE_EXPIRE[$storeKey]
      );
    }

    return $this->adapter->remove($storeKey);
  }

  /**
   * Remove all cached-items.
   *
   * @return bool
   */
  public function removeAll()
  {
    if (!$this->adapter instanceof iAdapter) {
      return false;
    }

    // remove static-cache
    if (!empty(self::$STATIC_CACHE)) {
      self::$STATIC_CACHE = array();
      self::$STATIC_CACHE_COUNTER = array();
      self::$STATIC_CACHE_EXPIRE = array();
    }

    return $this->adapter->removeAll();
  }

  /**
   * Check if cached-item exists.
   *
   * @param string $key
   *
   * @return boolean
   */
  public function existsItem($key)
  {
    if (!$this->adapter instanceof iAdapter) {
      return false;
    }

    $storeKey = $this->calculateStoreKey($key);

    // check static-cache
    if ($this->checkForStaticCache($storeKey) === true) {
      return true;
    }

    return $this->adapter->exists($storeKey);
  }

  /**
   * @param string $storeKey
   *
   * @return bool
   */
  protected function checkForStaticCache($storeKey)
  {
    if (
        !empty(self::$STATIC_CACHE)
        &&
        array_key_exists($storeKey, self::$STATIC_CACHE) === true
        &&
        array_key_exists($storeKey, self::$STATIC_CACHE_EXPIRE) === true
        &&
        time() <= self::$STATIC_CACHE_EXPIRE[$storeKey]
    ) {
      return true;
    }

    return false;
  }

  /**
   * Get the current adapter class-name.
   *
   * @return string
   */
  public function getUsedAdapterClassName()
  {
    return get_class($this->adapter);
  }

  /**
   * Get the current serializer class-name.
   *
   * @return string
   */
  public function getUsedSerializerClassName()
  {
    return get_class($this->serializer);
  }
}

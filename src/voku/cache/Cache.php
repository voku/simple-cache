<?php

namespace voku\cache;

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
  private $adapter;

  /**
   * @var iSerializer
   */
  private $serializer;

  /**
   * @var string
   */
  private $prefix = '';

  /**
   * @var bool
   */
  private $isReady = false;

  /**
   * @var bool
   */
  private $isActive = true;

  /**
   * @var mixed no cache, if admin-session is set
   */
  private $isAdminSession = false;

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
  public function __construct($adapter = null, $serializer = null, $checkForUser = true, $cacheEnabled = true, $isAdminSession = false)
  {
    $this->isAdminSession = $isAdminSession;

    // check for active-cache
    $this->setActive($cacheEnabled);
    if ($this->isActive === true && $checkForUser === true) {
      $this->setActive($this->isCacheActiveForTheCurrentUser());
    }

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

      // Memcache(d) has his own "serializer", so don't use it twice
      if (!is_object($serializer) && $serializer === null) {
        if (
            $adapter instanceof AdapterMemcached
            ||
            $adapter instanceof AdapterMemcache
        ) {
          $serializer = new SerializerNo();
        } else {
          // set serializer as default
          $serializer = new SerializerIgbinary();
        }
      }
    }

    // check if we will use the cache
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
        // server == client
          (
              isset($_SERVER['SERVER_ADDR'])
              &&
              $_SERVER['SERVER_ADDR'] == $this->getClientIp()
          )
          ||
          // admin is logged-in
          $this->isAdminSession
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
  private function getClientIp($trust_proxy_headers = false)
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
   * check for developer
   *
   * @return bool
   */
  private function checkForDev()
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
   * set the default-prefix via "SERVER"-var + "SESSION"-language
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
   * auto-connect to the available cache-system on the server
   *
   * @return iAdapter
   */
  protected function autoConnectToAvailableCacheSystem()
  {
    static $adapterCache;

    if (is_object($adapterCache) && $adapterCache instanceof iAdapter) {
      return $adapterCache;
    } else {

      $memcached = null;
      $isMemcachedAvailable = false;
      if (extension_loaded('memcached')) {
        $memcached = new \Memcached();
        $isMemcachedAvailable = $memcached->addServer('127.0.0.1', '11211');
      }

      if ($isMemcachedAvailable === false) {
        $memcached = null;
      }

      $adapterMemcached = new AdapterMemcached($memcached);
      if ($adapterMemcached->installed() === true) {

        // fallback to Memcached
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

          // fallback to Memcache
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

            // fallback to Redis
            $adapter = $adapterRedis;

          } else {

            $adapterXcache = new AdapterXcache();
            if ($adapterXcache->installed() === true) {

              // fallback to Xcache
              $adapter = $adapterXcache;

            } else {

              $adapterApc = new AdapterApc();
              if ($adapterApc->installed() === true) {

                // fallback to APC || APCu
                $adapter = $adapterApc;

              } else {

                $adapterFile = new AdapterFile();
                if ($adapterFile->installed() === true) {

                  // fallback to File-Cache
                  $adapter = $adapterFile;

                } else {
                  // no cache-adapter available -> use a array
                  $adapter = new AdapterArray();
                }
              }
            }
          }
        }
      }

      // save to static cache
      $adapterCache = $adapter;
    }

    return $adapter;
  }

  /**
   * set cacheIsReady state
   *
   * @param boolean $isReady
   */
  private function setCacheIsReady($isReady)
  {
    $this->isReady = (boolean)$isReady;
  }

  /**
   * get the cacheIsReady state
   *
   * @return boolean
   */
  public function getCacheIsReady()
  {
    return $this->isReady;
  }

  /**
   * get cached-item by key
   *
   * @param string $key
   *
   * @return mixed
   */
  public function getItem($key)
  {
    if ($this->adapter instanceof iAdapter) {
      $storeKey = $this->calculateStoreKey($key);
      $serialized = $this->adapter->get($storeKey);
      $value = $serialized ? $this->serializer->unserialize($serialized) : null;
    } else {
      return null;
    }

    return $value;
  }

  /**
   * calculate store-key (prefix + $rawKey)
   *
   * @param String $rawKey
   *
   * @return string
   */
  private function calculateStoreKey($rawKey)
  {
    $str = $this->getPrefix() . $rawKey;

    if ($this->adapter instanceof AdapterFile) {
      $str = $this->cleanStoreKey($str);
    }

    return $str;
  }

  /**
   * @param string $str
   *
   * @return string
   */
  private function cleanStoreKey($str)
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
   * @return string
   */
  public function getPrefix()
  {
    return $this->prefix;
  }

  /**
   * set prefix [WARNING: do not use if you don't know what you do]
   *
   * @param string $prefix
   */
  public function setPrefix($prefix)
  {
    $this->prefix = (string)$prefix;
  }

  /**
   * set cache-item by key => value + date
   *
   * @param string    $key
   * @param mixed     $value
   * @param \DateTime $date
   *
   * @return boolean
   * @throws \Exception
   */
  public function setItemToDate($key, $value, \DateTime $date)
  {
    $ttl = $date->getTimestamp() - time();

    if ($ttl <= 0) {
      throw new \Exception('Date in the past.');
    }

    $storeKey = $this->calculateStoreKey($key);

    return $this->setItem($storeKey, $value, $ttl);
  }

  /**
   * set cache-item by key => value + ttl
   *
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return bool
   */
  public function setItem($key, $value, $ttl = 0)
  {
    if (
        $this->adapter instanceof iAdapter
        &&
        $this->serializer instanceof iSerializer
    ) {
      $storeKey = $this->calculateStoreKey($key);
      $serialized = $this->serializer->serialize($value);

      if ($ttl) {
        return $this->adapter->setExpired($storeKey, $serialized, $ttl);
      } else {
        return $this->adapter->set($storeKey, $serialized);
      }
    } else {
      return false;
    }
  }

  /**
   * remove cached-item
   *
   * @param string $key
   *
   * @return bool
   */
  public function removeItem($key)
  {
    if ($this->adapter instanceof iAdapter) {
      $storeKey = $this->calculateStoreKey($key);

      return $this->adapter->remove($storeKey);
    } else {
      return false;
    }
  }

  /**
   * remove cache
   *
   * @return bool
   */
  public function removeAll()
  {
    if ($this->adapter instanceof iAdapter) {
      return $this->adapter->removeAll();
    } else {
      return false;
    }
  }

  /**
   * check if cached-item exists
   *
   * @param string $key
   *
   * @return boolean
   */
  public function existsItem($key)
  {
    if ($this->adapter instanceof iAdapter) {
      $storeKey = $this->calculateStoreKey($key);

      return $this->adapter->exists($storeKey);
    } else {
      return false;
    }
  }

}

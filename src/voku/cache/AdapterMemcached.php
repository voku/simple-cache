<?php

namespace voku\cache;

/**
 * AdapterMemcached: Memcached-adapter
 *
 * @package   voku\cache
 */
class AdapterMemcached implements iAdapter
{
  /**
   * @var bool
   */
  public $installed = false;

  /**
   * @var \Memcached
   */
  private $memcached;

  /**
   * __construct
   *
   * @param \Memcached $memcached
   */
  public function __construct($memcached)
  {
    if ($memcached instanceof \Memcached) {
      $this->memcached = $memcached;
      $this->installed = true;

      $this->setSettings();
    }
  }

  /**
   * set Memcached settings
   */
  private function setSettings()
  {
    // Use faster compression if available
    if (\Memcached::HAVE_IGBINARY) {
      $this->memcached->setOption(\Memcached::OPT_SERIALIZER, \Memcached::SERIALIZER_IGBINARY);
    }

    // Fix for "PHP: hhvm"
    if (
        defined(\Memcached::OPT_COMPRESSION_TYPE) === true
        &&
        defined(\Memcached::COMPRESSION_FASTLZ) === true
    ) {
      $this->memcached->setOption(\Memcached::OPT_COMPRESSION_TYPE, \Memcached::COMPRESSION_FASTLZ);
    }

    $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
    $this->memcached->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
    $this->memcached->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
    $this->memcached->setOption(\Memcached::OPT_NO_BLOCK, true);
    $this->memcached->setOption(\Memcached::OPT_TCP_NODELAY, true);
    $this->memcached->setOption(\Memcached::OPT_COMPRESSION, true);
    $this->memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 2);
  }

  /**
   * set cache-item by key => value
   *
   * @param string $key
   * @param mixed  $value
   *
   * @return mixed|void
   */
  public function set($key, $value)
  {
    // Make sure we are under the proper limit
    /*
    if (strlen($this->memcached->getOption(\Memcached::OPT_PREFIX_KEY) . $key) > 250) {
      throw new \Exception('The passed cache key is over 250 bytes');
    }
    */

    return $this->memcached->set($key, $value);
  }

  /**
   * set cache-item by key => value + ttl
   *
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return boolean
   */
  public function setExpired($key, $value, $ttl)
  {
    if ($ttl > 2592000) {
      $ttl = 2592000;
    }

    return $this->memcached->set($key, $value, $ttl);
  }

  /**
   * remove cached-item by key
   *
   * @param string $key
   *
   * @return boolean
   */
  public function remove($key)
  {
    return $this->memcached->delete($key);
  }

  /**
   * check if cached-item exists
   *
   * @param string $key
   *
   * @return bool
   */
  public function exists($key)
  {
    return $this->get($key) !== false;
  }

  /**
   * get cached-item by key
   *
   * @param String $key
   *
   * @return mixed
   */
  public function get($key)
  {
    static $memcachedCache;

    if (isset($memcachedCache[$key])) {
      return $memcachedCache[$key];
    } else {
      $return = $this->memcached->get($key);
      $memcachedCache[$key] = $return;
      return $return;
    }
  }

  /**
   * check if cache is installed
   *
   * @return boolean
   */
  public function installed()
  {
    return $this->installed;
  }

}

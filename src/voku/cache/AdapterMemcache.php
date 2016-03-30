<?php

namespace voku\cache;

/**
 * AdapterMemcache: Memcache-adapter
 *
 * @package   voku\cache
 */
class AdapterMemcache implements iAdapter
{
  /**
   * @var bool
   */
  public $installed = false;

  /**
   * @var \Memcache
   */
  private $memcache;

  /**
   * @var boolean
   */
  private $compressed = false;

  /**
   * __construct
   *
   * @param \Memcache $memcache
   */
  public function __construct($memcache)
  {
    if ($memcache instanceof \Memcache) {
      $this->memcache = $memcache;
      $this->installed = true;
    }
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
    return $this->memcache->set($key, $value, $this->getCompressedFlag());
  }

  /**
   * get compressed-flag
   *
   * @return int 2 || 0
   */
  private function getCompressedFlag()
  {
    return $this->isCompressed() ? MEMCACHE_COMPRESSED : 0;
  }

  /**
   * check if is compressed
   *
   * @return boolean
   */
  public function isCompressed()
  {
    return $this->compressed;
  }

  /**
   * set compressed
   *
   * @param mixed $value will be converted to boolean
   */
  public function setCompressed($value)
  {
    $this->compressed = (bool)$value;
  }

  /**
   * set cache-item by key => value + ttl
   *
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return mixed|void
   */
  public function setExpired($key, $value, $ttl)
  {
    if ($ttl > 2592000) {
      $ttl = 2592000;
    }

    return $this->memcache->set($key, $value, $this->getCompressedFlag(), $ttl);
  }

  /**
   * remove cached-item by key
   *
   * @param string $key
   *
   * @return mixed|void
   */
  public function remove($key)
  {
    return $this->memcache->delete($key);
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
      $return = $this->memcache->get($key);
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

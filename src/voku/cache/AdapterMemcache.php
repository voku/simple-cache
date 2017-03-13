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
   * @inheritdoc
   */
  public function exists($key)
  {
    return $this->get($key) !== false;
  }

  /**
   * @inheritdoc
   */
  public function get($key)
  {
    static $memcacheCache = array();
    static $memcacheCacheCounter = array();
    $staticCacheCounterHelper = 5;

    if (!isset($memcacheCacheCounter[$key])) {
      $memcacheCacheCounter[$key] = 0;
    }

    if ($memcacheCacheCounter[$key] < ($staticCacheCounterHelper + 1)) {
      $memcacheCacheCounter[$key]++;
    }

    if (array_key_exists($key, $memcacheCache) === true) {

      // get from static-cache
      return $memcacheCache[$key];

    } else {

      // get from cache-adapter
      $return = $this->memcache->get($key);

      // save into static-cache
      if ($memcacheCacheCounter[$key] >= $staticCacheCounterHelper) {
        $memcacheCache[$key] = $return;
      }

      return $return;
    }
  }

  /**
   * @inheritdoc
   */
  public function installed()
  {
    return $this->installed;
  }

  /**
   * @inheritdoc
   */
  public function remove($key)
  {
    return $this->memcache->delete($key);
  }

  /**
   * @inheritdoc
   */
  public function removeAll()
  {
    return $this->memcache->flush();
  }

  /**
   * @inheritdoc
   */
  public function set($key, $value)
  {
    return $this->memcache->set($key, $value, $this->getCompressedFlag());
  }

  /**
   * @inheritdoc
   */
  public function setExpired($key, $value, $ttl)
  {
    if ($ttl > 2592000) {
      $ttl = 2592000;
    }

    return $this->memcache->set($key, $value, $this->getCompressedFlag(), $ttl);
  }

  /**
   * Get the compressed-flag from MemCache.
   *
   * @return int 2 || 0
   */
  private function getCompressedFlag()
  {
    return $this->isCompressed() ? MEMCACHE_COMPRESSED : 0;
  }

  /**
   * Check if compression from MemCache is active.
   *
   * @return boolean
   */
  public function isCompressed()
  {
    return $this->compressed;
  }

  /**
   * Activate the compression from MemCache.
   *
   * @param mixed $value will be converted to boolean
   */
  public function setCompressed($value)
  {
    $this->compressed = (bool)$value;
  }

}

<?php

namespace voku\cache;

/**
 * AdapterXcache: Xcache-adapter
 *
 * @package voku\cache
 */
class AdapterXcache implements iAdapter
{
  public $installed = false;

  /**
   * __construct
   */
  public function __construct()
  {
    if (extension_loaded('xcache') === true) {
      $this->installed = true;
    }
  }

  /**
   * @inheritdoc
   */
  public function exists($key)
  {
    return xcache_isset($key);
  }

  /**
   * @inheritdoc
   */
  public function get($key)
  {
    return xcache_get($key);
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
    return xcache_unset($key);
  }

  /**
   * @inheritdoc
   */
  public function removeAll()
  {
    if (defined('XC_TYPE_VAR')) {
      $xCacheCount = xcache_count(XC_TYPE_VAR);
      for ($i = 0; $i < $xCacheCount; $i++) {
        xcache_clear_cache(XC_TYPE_VAR, $i);
      }

      return true;
    }

    return false;
  }

  /**
   * @inheritdoc
   */
  public function set($key, $value)
  {
    return xcache_set($key, $value);
  }

  /**
   * @inheritdoc
   */
  public function setExpired($key, $value, $ttl)
  {
    return xcache_set($key, $value, $ttl);
  }

}

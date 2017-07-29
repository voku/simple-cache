<?php

namespace voku\cache;

/**
 * AdapterApc: a APC-Cache adapter
 *
 * http://php.net/manual/de/book.apc.php
 *
 * @package voku\cache
 */
class AdapterApc implements iAdapter
{

  /**
   * @var bool
   */
  public $installed = false;

  /**
   * @var bool
   */
  public $debug = false;

  /**
   * __construct()
   */
  public function __construct()
  {
    if (
        function_exists('apc_store') === true
        &&
        ini_get('apc.enabled')
    ) {
      $this->installed = true;
    }
  }

  /**
   * Check if apc-cache exists.
   *
   * WARNING: use $this->exists($key) instead
   *
   * @param string $key
   *
   * @return bool
   *
   * @internal
   */
  public function apc_cache_exists($key)
  {
    return (bool)apc_fetch($key);
  }

  /**
   * Clears the APC cache by type.
   *
   * @param string $type - If $type is "user", the user cache will be cleared; otherwise,
   *                       the system cache (cached files) will be cleared.
   *
   * @return boolean
   *
   * @internal
   */
  public function cacheClear($type)
  {
    return apc_clear_cache($type);
  }

  /**
   * Retrieves cached information from APC's data store
   *
   * @param string  $type    - If $type is "user", information about the user cache will be returned.
   * @param boolean $limited - If $limited is TRUE, the return value will exclude the individual list of cache entries.
   *                         This is useful when trying to optimize calls for statistics gathering.
   *
   * @return array of cached data (and meta-data) or FALSE on failure.
   */
  public function cacheInfo($type = '', $limited = false)
  {
    return apc_cache_info($type, $limited);
  }

  /**
   * @inheritdoc
   */
  public function exists($key)
  {
    if (function_exists('apc_exists')) {
      return apc_exists($key);
    }

    return $this->apc_cache_exists($key);
  }

  /**
   * @inheritdoc
   */
  public function get($key)
  {
    if ($this->exists($key)) {
      return apc_fetch($key);
    }

    return false;
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
    return apc_delete($key);
  }

  /**
   * @inheritdoc
   */
  public function removeAll()
  {
    return $this->cacheClear('system') && $this->cacheClear('user');
  }

  /**
   * @inheritdoc
   */
  public function set($key, $value)
  {
    return apc_store($key, $value);
  }

  /**
   * @inheritdoc
   */
  public function setExpired($key, $data, $ttl)
  {
    return apc_store($key, $data, $ttl);
  }

}

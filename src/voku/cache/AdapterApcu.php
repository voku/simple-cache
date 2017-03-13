<?php

namespace voku\cache;

/**
 * AdapterApcu: a APCu-Cache adapter
 *
 * http://php.net/manual/de/book.apcu.php
 *
 * @package   voku\cache
 */
class AdapterApcu implements iAdapter
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
        function_exists('apcu_store') === true
        &&
        ini_get('apc.enabled')
    ) {
      $this->installed = true;
    }
  }

  /**
   * Check if apcu-cache exists.
   *
   * WARNING: use $this->exists($key) instead
   *
   * @param string $key
   *
   * @return bool
   *
   * @internal
   */
  public function apcu_cache_exists($key)
  {
    return (bool)apcu_fetch($key);
  }

  /**
   * Clears the APCu cache by type.
   *
   * @param string $type   - If $type is "user", the user cache will be cleared; otherwise,
   *                       the system cache (cached files) will be cleared.
   *
   * @return boolean
   *
   * @internal
   */
  public function cacheClear($type)
  {
    return apcu_clear_cache($type);
  }

  /**
   * Retrieves cached information from APCu's data store
   *
   * @param boolean $limited - If $limited is TRUE, the return value will exclude the individual list of cache entries.
   *                         This is useful when trying to optimize calls for statistics gathering.
   *
   * @return array of cached data (and meta-data) or FALSE on failure.
   */
  public function cacheInfo($limited = false)
  {
    return apcu_cache_info($limited);
  }

  /**
   * @inheritdoc
   */
  public function exists($key)
  {
    if (function_exists('apcu_exists')) {
      return apcu_exists($key);
    } else {
      return $this->apcu_cache_exists($key);
    }
  }

  /**
   * @inheritdoc
   */
  public function get($key)
  {
    if ($this->exists($key)) {
      return apc_fetch($key);
    } else {
      return false;
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
    return apcu_delete($key);
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
    return apcu_store($key, $value);
  }

  /**
   * @inheritdoc
   */
  public function setExpired($key, $data, $ttl)
  {
    return apcu_store($key, $data, $ttl);
  }

}

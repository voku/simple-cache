<?php

namespace voku\cache;

/**
 * AdapterApc: a APC-Cache adapter
 *
 * http://php.net/manual/de/book.apc.php
 *
 * @package   voku\cache
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
    if (function_exists('apc_store') === true) {
      $this->installed = true;
    }
  }

  /**
   * cacheInfo
   *
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

  /*
   * apc_cache_exists (fallback function for old apc)
   *
   * * @return boolean
   */

  /**
   * Cache a variable in the data-store
   *
   * @param string $key
   * @param mixed  $value
   *
   * @return Boolean - Returns TRUE on success or FALSE on failure.
   */
  public function set($key, $value)
  {
    return apc_store($key, $value);
  }

  /**
   * Cache a variable in the data-store with ttl
   *
   * @param string $key  - Store the variable using this name.
   * @param string $data - The variable to store.
   * @param string $ttl  - Time To Live; store var in the cache for ttl seconds. "0" for no ttl
   *
   * @return boolean - Returns TRUE on success or FALSE on failure.
   */
  public function setExpired($key, $data, $ttl)
  {
    return apc_store($key, $data, $ttl);
  }

  /**
   *
   * get stored value in APC from key
   *
   * @param string $key - The key used to store the value.
   *
   * @return boolean - The stored variable or array of variables on success; FALSE on failure.
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
   * Checks if APC key exists
   *
   * @param mixed $key - A string, or an array of strings, that contain keys.
   *
   * @return mixed - Returns TRUE if the key exists, otherwise FALSE Or if an array was passed to keys, then an array
   *               is returned that contains all existing keys, or an empty array if none exist.
   */
  public function exists($key)
  {
    if (function_exists('apc_exists')) {
      return apc_exists($key);
    } else {
      return $this->apc_cache_exists($key);
    }
  }

  /**
   * check if apc-cache exists
   *
   * @param string $key
   *
   * @return bool
   */
  public function apc_cache_exists($key)
  {
    return (bool)apc_fetch($key);
  }

  /**
   * Removes a stored variable from the cache
   *
   * @param string $key - The key used to store the value (with apc_store()).
   *
   * @return boolean - Returns TRUE on success or FALSE on failure.
   */
  public function remove($key)
  {
    return apc_delete($key);
  }

  /**
   * Removes the cache
   *
   * @return boolean - Returns TRUE on success or FALSE on failure.
   */
  public function removeAll()
  {
    return apc_clear_cache('system') && apc_clear_cache('user');
  }

  /**
   * Clears the APC cache
   *
   * @param string $type - If $type is "user", the user cache will be cleared; otherwise, the system cache (cached
   *                     files) will be cleared.
   *
   * @return boolean - Returns TRUE on success or FALSE on failure.
   */
  public function cacheClear($type)
  {
    return apc_clear_cache($type);
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

<?php

namespace voku\cache;

/**
 * AdapterArray: simple array-cache adapter
 *
 * @package   voku\cache
 */
class AdapterArray implements iAdapter
{

  /**
   * @var array
   */
  private static $values = array();

  /**
   * @var array
   */
  private static $expired = array();

  /**
   * get cached-item by key
   *
   * @param String $key
   *
   * @return mixed
   */
  public function get($key)
  {
    return $this->exists($key) ? self::$values[$key] : null;
  }

  /**
   * remove expired
   *
   * @param string $key
   *
   * @return boolean
   */
  private function removeExpired($key)
  {
    if (
        !isset(self::$expired[$key])
        ||
        !isset(self::$values[$key])
    ) {
      return false;
    }

    list($time, $ttl) = self::$expired[$key];

    if (time() > ($time + $ttl)) {
      unset(self::$values[$key]);
    }

    if (!isset(self::$values[$key])) {
      unset(self::$expired[$key]);
    }

    return true;
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
    $this->removeExpired($key);

    return isset(self::$values[$key]);
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
    self::$values[$key] = $value;
  }

  /**
   * set cache-item by key => value + ttl
   *
   * @param string $key
   * @param mixed  $value
   * @param        $ttl
   *
   * @return mixed|void
   */
  public function setExpired($key, $value, $ttl)
  {
    self::$values[$key] = $value;
    self::$expired[$key] = array(
        time(),
        $ttl
    );
  }

  /**
   * remove cache-item by key
   *
   * @param string $key
   *
   * @return mixed|void
   */
  public function remove($key)
  {
    $this->removeExpired($key);

    unset(self::$values[$key]);
  }

  /**
   * check if cache is installed
   *
   * @return true
   */
  public function installed()
  {
    return true;
  }

}

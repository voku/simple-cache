<?php

namespace voku\cache;

/**
 * AdapterArray: simple array-cache adapter
 *
 * @package voku\cache
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
   * @inheritdoc
   */
  public function exists($key)
  {
    $this->removeExpired($key);

    return array_key_exists($key, self::$values);
  }

  /**
   * @inheritdoc
   */
  public function get($key)
  {
    return $this->exists($key) ? self::$values[$key] : null;
  }

  /**
   * @inheritdoc
   */
  public function installed()
  {
    return true;
  }

  /**
   * @inheritdoc
   */
  public function remove($key)
  {
    $this->removeExpired($key);

    if (array_key_exists($key, self::$values) === true) {
      unset(self::$values[$key]);

      return true;
    }

    return false;
  }

  /**
   * @inheritdoc
   */
  public function removeAll()
  {
    self::$values = array();
    self::$expired = array();

    return true;
  }

  /**
   * @inheritdoc
   */
  public function set($key, $value)
  {
    self::$values[$key] = $value;

    return true;
  }

  /**
   * @inheritdoc
   */
  public function setExpired($key, $value, $ttl)
  {
    self::$values[$key] = $value;
    self::$expired[$key] = array(time(), $ttl);

    return true;
  }

  /**
   * Remove expired cache.
   *
   * @param string $key
   *
   * @return boolean
   */
  private function removeExpired($key)
  {
    if (
        array_key_exists($key, self::$expired) === false
        ||
        array_key_exists($key, self::$values) === false
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

}

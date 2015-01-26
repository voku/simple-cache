<?php

namespace voku\cache;

/**
 * AdapterArray: simple array-cache adapter
 *
 * @package   voku\cache
 */
class AdapterArray implements iAdapter
{

  private $values = array();
  private $expired = array();

  /**
   * get cached-item by key
   *
   * @param String $key
   *
   * @return mixed
   */
  public function get($key)
  {
    $this->removeExpired($key);

    return $this->exists($key) ? $this->values[$key] : null;
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
    if (!isset($this->expired[$key]) || !isset($this->values[$key])) {
      return false;
    }

    list($time, $ttl) = $this->expired[$key];

    if (time() > ($time + $ttl)) {
      unset($this->values[$key]);
    }

    if (!isset($this->values[$key])) {
      unset($this->expired[$key]);
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

    return isset($this->values[$key]);
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
    $this->values[$key] = $value;
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
    $this->values[$key] = $value;
    $this->expired[$key] = array(
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

    unset($this->values[$key]);
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

<?php

namespace voku\cache;

/**
 * iAdapter: cache-adapter interface
 *
 * @package   voku\cache
 */
interface iAdapter
{

  /**
   * get cache
   *
   * @param $key
   *
   * @return mixed
   */
  public function get($key);

  /**
   * set cache
   *
   * @param $key
   * @param $value
   *
   * @return mixed
   */
  public function set($key, $value);

  /**
   * set expired-cache
   *
   * @param $key
   * @param $value
   * @param $ttl
   *
   * @return mixed
   */
  public function setExpired($key, $value, $ttl);

  /**
   * remove cache
   *
   * @param $key
   *
   * @return mixed
   */
  public function remove($key);

  /**
   * check if cache exists
   *
   * @param $key
   *
   * @return mixed
   */
  public function exists($key);
}

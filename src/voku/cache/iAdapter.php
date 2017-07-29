<?php

namespace voku\cache;

/**
 * iAdapter: cache-adapter interface
 *
 * @package voku\cache
 */
interface iAdapter
{

  /**
   * Get cached-item by key.
   *
   * @param $key
   *
   * @return mixed
   */
  public function get($key);

  /**
   * Set cache-item by key => value.
   *
   * @param $key
   * @param $value
   *
   * @return bool
   */
  public function set($key, $value);

  /**
   * Set cache-item by key => value + ttl.
   *
   * @param $key
   * @param $value
   * @param $ttl
   *
   * @return bool
   */
  public function setExpired($key, $value, $ttl);

  /**
   * Remove cached-item by key.
   *
   * @param $key
   *
   * @return bool
   */
  public function remove($key);

  /**
   * Remove all cached items.
   *
   * @return bool
   */
  public function removeAll();

  /**
   * Check if cache-key exists.
   *
   * @param $key
   *
   * @return bool
   */
  public function exists($key);

  /**
   * Check if cache is installed.
   *
   * @return bool
   */
  public function installed();
}

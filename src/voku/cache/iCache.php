<?php

namespace voku\cache;

/**
 * iCache: cache-global interface
 *
 * @package voku\cache
 */
interface iCache
{

  /**
   * get item
   *
   * @param $key
   *
   * @return mixed
   */
  public function getItem($key);

  /**
   * set item
   *
   * @param      $key
   * @param      $value
   * @param null $ttl
   *
   * @return mixed
   */
  public function setItem($key, $value, $ttl = null);

  /**
   * set item a special expire-date
   *
   * @param           $key
   * @param           $value
   * @param \DateTime $date
   *
   * @return mixed
   */
  public function setItemToDate($key, $value, \DateTime $date);

  /**
   * remove item
   *
   * @param $key
   *
   * @return mixed
   */
  public function removeItem($key);

  /**
   * remove all items
   *
   * @return mixed
   */
  public function removeAll();

  /**
   * check if item exists
   *
   * @param $key
   *
   * @return mixed
   */
  public function existsItem($key);

}

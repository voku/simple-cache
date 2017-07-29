<?php

namespace voku\cache;

/**
 * iSerializer: cache-serializer interface
 *
 * @package voku\cache
 */
interface iSerializer
{

  /**
   * serialize
   *
   * @param $value
   *
   * @return mixed
   */
  public function serialize($value);

  /**
   * unserialize
   *
   * @param $value
   *
   * @return mixed
   */
  public function unserialize($value);
}

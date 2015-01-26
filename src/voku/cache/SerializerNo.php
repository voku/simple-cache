<?php

namespace voku\cache;

/**
 * SerializerNo: no serialize / unserialize !!!
 *
 * @package   voku\cache
 */
class SerializerNo implements iSerializer
{

  /**
   * serialize
   *
   * @param mixed $value
   *
   * @return string
   */
  public function serialize($value)
  {
    return $value;
  }

  /**
   * unserialize
   *
   * @param string $value
   *
   * @return mixed
   */
  public function unserialize($value)
  {
    return $value;
  }

}

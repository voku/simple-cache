<?php

namespace voku\cache;

/**
 * SerializerDefault: simple serialize / unserialize
 *
 * @package   voku\cache
 */
class SerializerDefault implements iSerializer
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
    return serialize($value);
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
    return unserialize($value);
  }

}

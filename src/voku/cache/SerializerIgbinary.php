<?php

namespace voku\cache;

/**
 * SerializerIgbinary: serialize / unserialize
 *

 * @package   voku\cache
 */
class SerializerIgbinary implements iSerializer
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
    if (function_exists('igbinary_serialize')) {
      return igbinary_serialize($value);
    } else {
      // fallback
      return serialize($value);
    }
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
    if (function_exists('igbinary_unserialize')) {
      return igbinary_unserialize($value);
    } else {
      // fallback
      return unserialize($value);
    }
  }

}

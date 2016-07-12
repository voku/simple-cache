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
   * @var bool
   */
  public static $_exists_igbinary;

  /**
   * SerializerIgbinary constructor.
   */
  public function __construct()
  {
    self::$_exists_igbinary = (function_exists('igbinary_serialize') && function_exists('igbinary_unserialize'));
  }

  /**
   * serialize
   *
   * @param mixed $value
   *
   * @return string
   */
  public function serialize($value)
  {
    if (self::$_exists_igbinary === true) {
      /** @noinspection PhpUndefinedFunctionInspection */
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
    if (self::$_exists_igbinary === true) {
      /** @noinspection PhpUndefinedFunctionInspection */
      return igbinary_unserialize($value);
    } else {
      // fallback
      return unserialize($value);
    }
  }

}

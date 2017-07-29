<?php

namespace voku\cache;

/**
 * SerializerIgbinary: serialize / unserialize
 *

 * @package voku\cache
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
   * @inheritdoc
   */
  public function serialize($value)
  {
    if (self::$_exists_igbinary === true) {
      /** @noinspection PhpUndefinedFunctionInspection */
      return igbinary_serialize($value);
    }

    // fallback
    return serialize($value);
  }

  /**
   * @inheritdoc
   */
  public function unserialize($value)
  {
    if (self::$_exists_igbinary === true) {
      /** @noinspection PhpUndefinedFunctionInspection */
      return igbinary_unserialize($value);
    }

    // fallback
    return unserialize($value);
  }

}

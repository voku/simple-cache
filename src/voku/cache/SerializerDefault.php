<?php

declare(strict_types=1);

namespace voku\cache;

/**
 * SerializerDefault: simple serialize / unserialize
 *
 * @package voku\cache
 */
class SerializerDefault implements iSerializer
{

  /**
   * @inheritdoc
   */
  public function serialize($value)
  {
    return serialize($value);
  }

  /**
   * @inheritdoc
   */
  public function unserialize($value)
  {
    return unserialize($value);
  }

}

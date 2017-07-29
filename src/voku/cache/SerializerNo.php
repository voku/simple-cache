<?php

namespace voku\cache;

/**
 * SerializerNo: no serialize / unserialize !!!
 *
 * @package voku\cache
 */
class SerializerNo implements iSerializer
{

  /**
   * @inheritdoc
   */
  public function serialize($value)
  {
    return $value;
  }

  /**
   * @inheritdoc
   */
  public function unserialize($value)
  {
    return $value;
  }

}

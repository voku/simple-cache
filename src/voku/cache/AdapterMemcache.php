<?php

namespace voku\cache;

use voku\cache\Exception\InvalidArgumentException;

/**
 * AdapterMemcache: Memcache-adapter
 *
 * @package voku\cache
 */
class AdapterMemcache implements iAdapter
{
  /**
   * @var bool
   */
  public $installed = false;

  /**
   * @var \Memcache
   */
  private $memcache;

  /**
   * @var boolean
   */
  private $compressed = false;

  /**
   * __construct
   *
   * @param \Memcache|null $memcache
   */
  public function __construct($memcache = null)
  {
    if ($memcache instanceof \Memcache) {
      $this->setMemcache($memcache);
    }
  }

  /**
   * @param \Memcache $memcache
   */
  public function setMemcache(\Memcache $memcache) {
    $this->memcache = $memcache;
    $this->installed = true;
  }

  /**
   * @inheritdoc
   */
  public function exists($key)
  {
    return $this->get($key) !== false;
  }

  /**
   * @inheritdoc
   */
  public function get($key)
  {
    return $this->memcache->get($key);
  }

  /**
   * @inheritdoc
   */
  public function installed()
  {
    return $this->installed;
  }

  /**
   * @inheritdoc
   */
  public function remove($key)
  {
    return $this->memcache->delete($key);
  }

  /**
   * @inheritdoc
   */
  public function removeAll()
  {
    return $this->memcache->flush();
  }

  /**
   * @inheritdoc
   */
  public function set($key, $value)
  {
    // Make sure we are under the proper limit
    if (strlen($key) > 250) {
      throw new InvalidArgumentException('The passed cache key is over 250 bytes:' . print_r($key, true));
    }

    return $this->memcache->set($key, $value, $this->getCompressedFlag());
  }

  /**
   * @inheritdoc
   */
  public function setExpired($key, $value, $ttl)
  {
    if ($ttl > 2592000) {
      $ttl = 2592000;
    }

    return $this->memcache->set($key, $value, $this->getCompressedFlag(), $ttl);
  }

  /**
   * Get the compressed-flag from MemCache.
   *
   * @return int 2 || 0
   */
  private function getCompressedFlag()
  {
    return $this->isCompressed() ? MEMCACHE_COMPRESSED : 0;
  }

  /**
   * Check if compression from MemCache is active.
   *
   * @return boolean
   */
  public function isCompressed()
  {
    return $this->compressed;
  }

  /**
   * Activate the compression from MemCache.
   *
   * @param mixed $value will be converted to boolean
   */
  public function setCompressed($value)
  {
    $this->compressed = (bool)$value;
  }

}

<?php

declare(strict_types=1);

namespace voku\cache;

/**
 * CacheChain: global-cache-chain class
 *
 * @package voku\cache
 */
class CacheChain implements iCache
{

  /**
   * @var array iCache
   */
  private $caches = array();

  /**
   * __construct
   *
   * @param array $caches
   */
  public function __construct(array $caches = array())
  {
    array_map(
        array(
            $this,
            'addCache'
        ), $caches
    );
  }

  /**
   * get caches
   *
   * @return array
   */
  public function getCaches()
  {
    return $this->caches;
  }

  /**
   * add cache
   *
   * @param iCache  $cache
   * @param bool $prepend
   *
   * @throws \InvalidArgumentException
   */
  public function addCache(iCache $cache, $prepend = true)
  {
    if ($this === $cache) {
      throw new \InvalidArgumentException('loop-error, put into other cache');
    }

    if ($prepend) {
      array_unshift($this->caches, $cache);
    } else {
      $this->caches[] = $cache;
    }
  }

  /**
   * @inheritdoc
   */
  public function getItem(string $key)
  {
    /* @var $cache iCache */
    foreach ($this->caches as $cache) {
      if ($cache->existsItem($key)) {
        return $cache->getItem($key);
      }
    }

    return null;
  }

  /**
   * @inheritdoc
   */
  public function setItem(string $key, $value, $ttl = null): bool
  {
    /* @var $cache iCache */
    foreach ($this->caches as $cache) {
      $cache->setItem($key, $value, $ttl);
    }
  }

  /**
   * @inheritdoc
   */
  public function setItemToDate(string $key, $value, \DateTime $date)
  {
    /* @var $cache iCache */
    foreach ($this->caches as $cache) {
      $cache->setItemToDate($key, $value, $date);
    }
  }

  /**
   * @inheritdoc
   */
  public function removeItem(string $key): bool
  {
    /* @var $cache iCache */
    foreach ($this->caches as $cache) {
      $cache->removeItem($key);
    }
  }

  /**
   * @inheritdoc
   */
  public function existsItem(string $key): bool
  {
    /* @var $cache iCache */
    foreach ($this->caches as $cache) {
      if ($cache->existsItem($key)) {
        return true;
      }
    }

    return false;
  }

  /**
   * @inheritdoc
   */
  public function removeAll(): bool
  {
    /* @var $cache iCache */
    foreach ($this->caches as $cache) {
      $cache->removeAll();
    }
  }
}

<?php

namespace voku\cache;

use Predis\Client;

/**
 * AdapterPredis: Memcached-adapter
 *
 * @package   voku\cache
 */
class AdapterPredis implements iAdapter
{
  /**
   * @var Client
   */
  private $client;

  /**
   * @param Client $client
   */
  public function __construct(Client $client)
  {
      $this->client = $client;
  }

  /**
   * get
   *
   * @param $key
   *
   * @return string
   */
  public function get($key)
  {
    return $this->client->get($key);
  }

  /**
   * set
   *
   * @param $key
   * @param $value
   *
   * @return mixed
   */
  public function set($key, $value)
  {
    return $this->client->set($key, $value);
  }

  /**
   * set expired
   *
   * @param $key
   * @param $value
   * @param $ttl
   *
   * @return int
   */
  public function setExpired($key, $value, $ttl)
  {
    return $this->client->setex($key, $ttl, $value);
  }

  /**
   * remove
   *
   * @param $key
   *
   * @return int
   */
  public function remove($key)
  {
    return $this->client->del($key);
  }

  /**
   * @param $key
   *
   * @return int
   */
  public function exists($key)
  {
    return $this->client->exists($key);
  }
}

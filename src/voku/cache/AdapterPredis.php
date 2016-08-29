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
   * @var bool
   */
  public $installed = false;

  /**
   * @var Client
   */
  private $client;

  /**
   * @param Client $client
   */
  public function __construct($client)
  {
    if ($client instanceof Client) {
      $this->installed = true;
      $this->client = $client;
    }
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
   * remove all
   *
   * @return bool
   */
  public function removeAll()
  {
    return $this->client->flushall();
  }

  /**
   * check if cache is installed
   *
   * @return boolean
   */
  public function installed()
  {
    return $this->installed;
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

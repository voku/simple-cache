<?php

namespace voku\cache;

use Predis\Client;

/**
 * AdapterPredis: Memcached-adapter
 *
 * @package voku\cache
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
   * @param Client|null $client
   */
  public function __construct($client = null)
  {
    if ($client instanceof Client) {
      $this->setPredisClient($client);
    }
  }

  /**
   * @param Client $client
   */
  public function setPredisClient(Client $client)
  {
    $this->installed = true;
    $this->client = $client;
  }


  /**
   * @inheritdoc
   */
  public function exists($key)
  {
    return $this->client->exists($key);
  }

  /**
   * @inheritdoc
   */
  public function get($key)
  {
    return $this->client->get($key);
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
    return $this->client->del($key);
  }

  /**
   * @inheritdoc
   */
  public function removeAll()
  {
    return $this->client->flushall();
  }

  /**
   * @inheritdoc
   */
  public function set($key, $value)
  {
    return $this->client->set($key, $value);
  }

  /**
   * @inheritdoc
   */
  public function setExpired($key, $value, $ttl)
  {
    return $this->client->setex($key, $ttl, $value);
  }
}

<?php

use voku\cache\AdapterMemcache;
use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;
use voku\cache\SerializerNo;

/**
 * MemcacheCacheTest
 */
class MemcacheCacheTest extends \PHPUnit\Framework\TestCase
{

  /**
   * @var iSerializer
   */
  public $serializer;

  /**
   * @var iAdapter
   */
  public $adapter;

  /**
   * @var Cache
   */
  public $cache;

  protected $backupGlobalsBlacklist = array(
      '_SESSION',
  );

  public function testSetItem()
  {
    $return = $this->cache->setItem('foo', array(1, 2, 3, 4));

    self::assertSame(true, $return);
  }

  public function testGetItem()
  {
    $return = $this->cache->getItem('foo');

    self::assertSame(array(1, 2, 3, 4), $return);
  }

  public function testExistsItem()
  {
    $return = $this->cache->existsItem('foo');

    self::assertSame(true, $return);
  }

  public function testGetCacheIsReady()
  {
    $return = $this->cache->getCacheIsReady();

    self::assertSame(true, $return);
  }

  public function testSetGetItemWithPrefix()
  {
    $this->cache->setPrefix('bar');
    $prefix = $this->cache->getPrefix();
    self::assertSame('bar', $prefix);

    $return = $this->cache->setItem('foo', array(3, 2, 1));
    self::assertSame(true, $return);

    $return = $this->cache->getItem('foo');
    self::assertSame(array(3, 2, 1), $return);
  }

  public function testGetUsedAdapterClassName()
  {
    self::assertSame('voku\cache\AdapterMemcache', $this->cache->getUsedAdapterClassName());
  }

  public function testGetUsedSerializerClassName()
  {
    self::assertSame('voku\cache\SerializerNo', $this->cache->getUsedSerializerClassName());
  }

  public function testSetGetCacheWithEndDateTime()
  {
    $expireDate = new DateTime();
    $interval = DateInterval::createFromDateString('+3 seconds');
    $expireDate->add($interval);

    $return = $this->cache->setItemToDate('testSetGetCacheWithEndDateTime', array(3, 2, 1), $expireDate);
    self::assertSame(true, $return);

    $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
    self::assertSame(array(3, 2, 1), $return);
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    $memcache = null;
    $isMemcacheAvailable = false;
    if (class_exists('\Memcache')) {
      $memcache = new \Memcache;
      /** @noinspection PhpUsageOfSilenceOperatorInspection */
      $isMemcacheAvailable = @$memcache->connect('127.0.0.1', 11211);
    }

    if ($isMemcacheAvailable === false) {
      $memcache = null;
    }

    $this->adapter = new AdapterMemcache($memcache);
    $this->serializer = new SerializerNo();

    if ($this->adapter->installed() === false) {
      self::markTestSkipped(
          'The Memcache extension is not available.'
      );
    }

    $this->cache = new Cache($this->adapter, $this->serializer, false, true);

    // reset default prefix
    $this->cache->setPrefix('');
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown()
  {

  }

}

<?php

use voku\cache\AdapterFile;
use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;
use voku\cache\SerializerDefault;

/**
 * FileCacheTest
 */
class FileCacheTest extends PHPUnit_Framework_TestCase
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
    $return = $this->cache->setItem('2571_519ä#-`9de.foo::bar', array(1, 2, 3));
    self::assertTrue($return);

    $return = $this->cache->setItem('2571_519ä#-`9de.foo::bar', array(1, 2));
    self::assertTrue($return);

    $return = $this->cache->setItem('2571_519ä#-`9de.foo::bar', array(1, 2, 3, 4));
    self::assertTrue($return);
  }

  public function testGetItem()
  {
    for ($i = 0; $i <= 20; $i++) {
      $return = $this->cache->getItem('2571_519ä#-`9de.foo::bar');

      self::assertSame(array(1, 2, 3, 4), $return);
    }
  }

  public function testExistsItem()
  {
    $return = $this->cache->existsItem('2571_519ä#-`9de.foo::bar');

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

  public function testRemove()
  {
    $return = $this->cache->setItem('foobar_test', array(4, 2, 1));
    self::assertSame(true, $return);

    $return = $this->cache->setItem('foobar_test_v2', array(5, 2, 1));
    self::assertSame(true, $return);

    $return = $this->cache->setItem('foobar_test_v3', array(6, 2, 1));
    self::assertSame(true, $return);

    $return = $this->cache->getItem('foobar_test');
    self::assertSame(array(4, 2, 1), $return);

    $return = $this->cache->getItem('foobar_test_v2');
    self::assertSame(array(5, 2, 1), $return);

    $return = $this->cache->getItem('foobar_test_v3');
    self::assertSame(array(6, 2, 1), $return);

    // -- remove one item

    $return = $this->cache->removeItem('foobar_test');
    self::assertSame(true, $return);

    // -- remove one item - test

    $return = $this->cache->getItem('foobar_test');
    self::assertSame(null, $return);

    $return = $this->cache->getItem('foobar_test_v2');
    self::assertSame(array(5, 2, 1), $return);

    // -- remove all

    $return = $this->cache->removeAll();
    self::assertTrue($return);

    // -- remove all - tests

    $return = $this->cache->getItem('foobar_test');
    self::assertSame(null, $return);

    $return = $this->cache->getItem('foobar_test_v2');
    self::assertSame(null, $return);

    $return = $this->cache->getItem('foobar_test_v3');
    self::assertSame(null, $return);
  }


  public function testSetGetCacheWithEndDateTimeAndStaticCacheAuto()
  {
    $expireDate = new DateTime();
    $interval = DateInterval::createFromDateString('+1 seconds');
    $expireDate->add($interval);

    $return = $this->cache->setItemToDate('testSetGetCacheWithEndDateTime', array(3, 2, 1), $expireDate);
    self::assertSame(true, $return);

    for ($i = 0; $i <= 20; $i++) {
      $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
      self::assertSame(array(3, 2, 1), $return);
    }

    sleep(2);

    $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
    self::assertSame(null, $return);
  }

  public function testSetGetCacheWithEndDateTimeAndStaticCacheForce()
  {
    $expireDate = new DateTime();
    $interval = DateInterval::createFromDateString('+1 seconds');
    $expireDate->add($interval);

    $return = $this->cache->setItemToDate('testSetGetCacheWithEndDateTime', array(3, 2, 1), $expireDate);
    self::assertSame(true, $return);

    for ($i = 0; $i <= 4; $i++) {
      $return = $this->cache->getItem('testSetGetCacheWithEndDateTime', 2);
      self::assertSame(array(3, 2, 1), $return);
    }

    sleep(2);

    $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
    self::assertSame(null, $return);
  }

  public function testGetUsedAdapterClassName()
  {
    self::assertSame('voku\cache\AdapterFile', $this->cache->getUsedAdapterClassName());
  }

  public function testGetUsedSerializerClassName()
  {
    self::assertSame('voku\cache\SerializerDefault', $this->cache->getUsedSerializerClassName());
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    $this->adapter = new AdapterFile();
    $this->serializer = new SerializerDefault();

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

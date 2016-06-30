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
    $return = $this->cache->setItem('2571_519ä#-`9de.foo::bar', array(1, 2, 3, 4));

    self::assertEquals(true, $return);
  }

  public function testGetItem()
  {
    $return = $this->cache->getItem('2571_519ä#-`9de.foo::bar');

    self::assertEquals(array(1, 2, 3, 4), $return);
  }

  public function testExistsItem()
  {
    $return = $this->cache->existsItem('2571_519ä#-`9de.foo::bar');

    self::assertEquals(true, $return);
  }

  public function testGetCacheIsReady()
  {
    $return = $this->cache->getCacheIsReady();

    self::assertEquals(true, $return);
  }

  public function testSetGetItemWithPrefix()
  {
    $this->cache->setPrefix('bar');
    $prefix = $this->cache->getPrefix();
    self::assertEquals('bar', $prefix);

    $return = $this->cache->setItem('foo', array(3, 2, 1));
    self::assertEquals(true, $return);

    $return = $this->cache->getItem('foo');
    self::assertEquals(array(3, 2, 1), $return);
  }

  public function testSetGetCacheWithEndDateTime()
  {
    $expireDate = new DateTime();
    $interval = DateInterval::createFromDateString('+3 seconds');
    $expireDate->add($interval);

    $return = $this->cache->setItemToDate('testSetGetCacheWithEndDateTime', array(3, 2, 1), $expireDate);
    self::assertEquals(true, $return);

    $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
    self::assertEquals(array(3, 2, 1), $return);
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

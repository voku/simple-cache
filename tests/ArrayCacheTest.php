<?php

use voku\cache\AdapterArray;
use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;
use voku\cache\SerializerDefault;

/**
 * ArrayCacheTest
 */
class ArrayCacheTest extends PHPUnit_Framework_TestCase
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

  public function testSetItemOfNull()
  {
    $return = $this->cache->setItem('foo_null', null);

    self::assertSame(true, $return);

    // -----

    $return = $this->cache->getItem('foo_null');
    self::assertSame(null, $return);
  }

  public function testSetItem()
  {
    $return = $this->cache->setItem('foo', array(1, 2, 3, 4));
    self::assertSame(true, $return);

    $return = $this->cache->getItem('foo');
    self::assertSame(array(1, 2, 3, 4), $return);

    // -----

    $ao = new ArrayObject();

    $ao->prop = 'prop data';
    $ao['arr'] = 'array data';

    $return = $this->cache->setItem('ao', $ao);

    self::assertSame(true, $return);
  }

  public function testGetItem()
  {
    $return = $this->cache->getItem('foo');

    self::assertSame(array(1, 2, 3, 4), $return);

    // -----

    $return = $this->cache->getItem('ao');

    $ao = new ArrayObject();

    $ao->prop = 'prop data';
    $ao['arr'] = 'array data';

    self::assertSame(true, $ao == $return);
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

  public function testSetGetCacheWithEndDateTime()
  {
    $expireDate = new DateTime();
    $interval = DateInterval::createFromDateString('+3 seconds');
    $expireDate->add($interval);

    $return = $this->cache->setItemToDate('testSetGetCacheWithEndDateTime', array(3, 2, 1), $expireDate);
    self::assertSame(true, $return);

    $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
    self::assertSame(array(3, 2, 1), $return);

    sleep(4);

    $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
    self::assertSame(null, $return);
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    $this->adapter = new AdapterArray();
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

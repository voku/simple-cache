<?php

use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;

/**
 * CacheTest
 */
class CacheTest extends PHPUnit_Framework_TestCase
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

  public function testKeyPrefix()
  {
    $prefix = 'prefix:';

    $this->cache->setPrefix($prefix);
    $this->adapter->expects(self::once())
        ->method('get')
        ->with(self::equalTo('prefix:lall'));

    $this->cache->getItem('lall');
  }

  public function testGetNotExists()
  {
    $key = 'some:test:key';

    $this->adapter->expects(self::once())
        ->method('get')
        ->with(self::equalTo('some:test:key'))
        ->will(self::returnValue(null));

    $actual = $this->cache->getItem($key);

    self::assertNull($actual);
  }

  public function testGet()
  {
    $key = 'some:test:key';
    $expected = uniqid(time(), true);

    $this->adapter->expects(self::once())
        ->method('get')
        ->with(self::equalTo($key))
        ->will(self::returnValue($expected));

    $this->serializer->expects(self::once())
        ->method('unserialize')
        ->with(self::equalTo($expected))
        ->will(self::returnValue($expected));

    $actual = $this->cache->getItem($key);

    self::assertSame($expected, $actual);
  }

  public function testSet()
  {
    $key = 'some:test:key';
    $value = uniqid(time(), true);

    $this->serializer->expects(self::once())
        ->method('serialize')
        ->with(self::equalTo($value))
        ->will(self::returnValue($value));

    $this->adapter->expects(self::once())
        ->method('setExpired')
        ->with(self::equalTo($key), self::equalTo($value));

    $this->cache->setItem($key, $value, 10);
  }

  public function testSetWithTtl()
  {
    $key = 'some:test:key';
    $value = uniqid(time(), true);
    $ttl = mt_rand(20, 5000);

    $this->serializer->expects(self::once())
        ->method('serialize')
        ->with(self::equalTo($value))
        ->will(self::returnValue($value));

    $this->adapter->expects(self::once())
        ->method('setExpired')
        ->with(self::equalTo($key), self::equalTo($value), self::equalTo($ttl));

    $this->cache->setItem($key, $value, $ttl);
  }

  public function testSetToDate()
  {
    $key = 'some:test:key';
    $value = uniqid(time(), true);
    $date = new DateTime();
    $time = $date->getTimestamp();
    $date->add(new DateInterval('PT1H'));

    $this->serializer->expects(self::once())
        ->method('serialize')
        ->with(self::equalTo($value))
        ->will(self::returnValue($value));

    $this->adapter->expects(self::once())
        ->method('setExpired')
        ->with(self::equalTo($key), self::equalTo($value), self::equalTo($date->getTimestamp() - $time));

    $this->cache->setItemToDate($key, $value, $date);
  }

  /**
   * @expectedException Exception
   */
  public function testSetWrongDate()
  {
    $key = 'some:test:key';
    $value = uniqid(time(), true);
    $date = new DateTime();
    $date->sub(new DateInterval('PT1H'));

    $this->cache->setItemToDate($key, $value, $date);
  }

  public function testExists()
  {
    $key = 'some:test:key';

    $this->adapter->expects(self::once())
        ->method('remove')
        ->with(self::equalTo($key));

    $this->cache->removeItem($key);
  }

  public function testRemove()
  {
    $key = 'some:test:key';

    $this->adapter->expects(self::once())
        ->method('exists')
        ->with(self::equalTo($key));

    $this->cache->existsItem($key);
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    $this->adapter = $this->getMock('voku\cache\AdapterApc');
    $this->serializer = $this->getMock('voku\cache\SerializerDefault');

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

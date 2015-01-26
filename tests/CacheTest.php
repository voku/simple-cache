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
    $this->adapter->expects($this->once())
        ->method('get')
        ->with($this->equalTo('prefix:lall'));

    $this->cache->getItem('lall');
  }

  public function testGetNotExists()
  {
    $key = 'some:test:key';

    $this->adapter->expects($this->once())
        ->method('get')
        ->with($this->equalTo($key))
        ->will($this->returnValue(null));

    $actual = $this->cache->getItem($key);

    $this->assertNull($actual);
  }

  public function testGet()
  {
    $key = 'some:test:key';
    $expected = uniqid(time());

    $this->adapter->expects($this->once())
        ->method('get')
        ->with($this->equalTo($key))
        ->will($this->returnValue($expected));

    $this->serializer->expects($this->once())
        ->method('unserialize')
        ->with($this->equalTo($expected))
        ->will($this->returnValue($expected));

    $actual = $this->cache->getItem($key);

    $this->assertEquals($expected, $actual);
  }

  public function testSet()
  {
    $key = 'some:test:key';
    $value = uniqid(time());

    $this->serializer->expects($this->once())
        ->method('serialize')
        ->with($this->equalTo($value))
        ->will($this->returnValue($value));

    $this->adapter->expects($this->once())
        ->method('setExpired')
        ->with($this->equalTo($key), $this->equalTo($value));

    $this->cache->setItem($key, $value, 10);
  }

  public function testSetWithTtl()
  {
    $key = 'some:test:key';
    $value = uniqid(time());
    $ttl = rand(20, 5000);

    $this->serializer->expects($this->once())
        ->method('serialize')
        ->with($this->equalTo($value))
        ->will($this->returnValue($value));

    $this->adapter->expects($this->once())
        ->method('setExpired')
        ->with($this->equalTo($key), $this->equalTo($value), $this->equalTo($ttl));

    $this->cache->setItem($key, $value, $ttl);
  }

  public function testSetToDate()
  {
    $key = 'some:test:key';
    $value = uniqid(time());
    $date = new DateTime();
    $time = $date->getTimestamp();
    $date->add(new DateInterval('PT1H'));

    $this->serializer->expects($this->once())
        ->method('serialize')
        ->with($this->equalTo($value))
        ->will($this->returnValue($value));

    $this->adapter->expects($this->once())
        ->method('setExpired')
        ->with($this->equalTo($key), $this->equalTo($value), $this->equalTo($date->getTimestamp() - $time));

    $this->cache->setItemToDate($key, $value, $date);
  }

  /**
   * @expectedException Exception
   */
  public function testSetWrongDate()
  {
    $key = 'some:test:key';
    $value = uniqid(time());
    $date = new DateTime();
    $date->sub(new DateInterval('PT1H'));

    $this->cache->setItemToDate($key, $value, $date);
  }

  public function testExists()
  {
    $key = 'some:test:key';

    $this->adapter->expects($this->once())
        ->method('remove')
        ->with($this->equalTo($key));

    $this->cache->removeItem($key);
  }

  public function testRemove()
  {
    $key = 'some:test:key';

    $this->adapter->expects($this->once())
        ->method('exists')
        ->with($this->equalTo($key));

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

    $this->cache = new Cache($this->adapter, $this->serializer, false, false);

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

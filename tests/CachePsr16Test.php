<?php

use voku\cache\CachePsr16;
use voku\cache\iAdapter;
use voku\cache\iSerializer;

/**
 * CachePsr16Test
 */
class CachePsr16Test extends PHPUnit_Framework_TestCase
{

  /**
   * @var iSerializer|PHPUnit_Framework_MockObject_MockObject
   */
  public $serializer;

  /**
   * @var iAdapter|PHPUnit_Framework_MockObject_MockObject
   */
  public $adapter;

  /**
   * @var CachePsr16
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
                  ->method('exists')
                  ->with(self::equalTo($prefix . 'lall'));

    $this->cache->get('lall');
  }

  public function testGetNotExists()
  {
    $key = 'some:test:key';

    $this->adapter->expects(self::once())
        ->method('exists')
        ->with(self::equalTo($key))
        ->will(self::returnValue(false));

    $actual = $this->cache->get($key, null);

    self::assertNull($actual);
  }

  public function testGet()
  {
    $key = 'some:test:key';
    $expected = uniqid(time(), true);

    $this->cache->set($key, $expected);

    $this->adapter->expects(self::once())
                  ->method('exists')
                  ->with(self::equalTo($key))
                  ->will(self::returnValue(true));

    $this->adapter->expects(self::once())
                  ->method('get')
                  ->with(self::equalTo($key))
                  ->will(self::returnValue($expected));

    $this->serializer->expects(self::once())
                     ->method('unserialize')
                     ->with(self::equalTo($expected))
                     ->will(self::returnValue($expected));

    $actual = $this->cache->get($key);

    self::assertSame($expected, $actual);
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

    $this->cache->set($key, $value, $ttl);
  }

  public function testSetWithTtlDateInterval()
  {
    $key = 'some:test:key';
    $value = uniqid(time(), true);
    $ttl = new DateInterval('PT1H');

    $this->serializer->expects(self::once())
                     ->method('serialize')
                     ->with(self::equalTo($value))
                     ->will(self::returnValue($value));

    $this->adapter->expects(self::once())
                  ->method('setExpired')
                  ->with(self::equalTo($key), self::equalTo($value));

    $this->cache->set($key, $value, $ttl);
  }

  public function testRemove()
  {
    $key = 'some:test:key';

    $this->adapter->expects(self::once())
        ->method('remove')
        ->with(self::equalTo($key));

    $this->cache->delete($key);
  }

  public function testExists()
  {
    $key = 'some:test:key';

    $this->adapter->expects(self::once())
        ->method('exists')
        ->with(self::equalTo($key));

    $this->cache->has($key);
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    if (method_exists($this, 'createMock')) {
      $this->adapter = $this->createMock('voku\cache\AdapterArray');
      $this->serializer = $this->createMock('voku\cache\SerializerDefault');
    } else {
      $this->adapter = $this->getMock('voku\cache\AdapterArray');
      $this->serializer = $this->getMock('voku\cache\SerializerDefault');
    }

    $this->cache = new CachePsr16($this->adapter, $this->serializer, false, true);

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

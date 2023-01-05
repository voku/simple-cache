<?php

use Voku\Cache\Cache;
use Voku\Cache\iAdapter;
use Voku\Cache\iSerializer;

/**
 * CacheTest
 *
 * @internal
 */
final class CacheTest extends \PHPUnit\Framework\TestCase
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
     * @var Cache
     */
    public $cache;

    protected $backupGlobalsBlacklist = [
        '_SESSION',
    ];

    public function testKeyPrefix()
    {
        $prefix = 'prefix:';

        $this->cache->setPrefix($prefix);
        $this->adapter->expects(static::once())
                  ->method('get')
                  ->with(static::equalTo($prefix . 'lall'));

        $this->cache->getItem('lall');
    }

    public function testGetNotExists()
    {
        $key = 'some:test:key';

        $this->adapter->expects(static::once())
                  ->method('get')
                  ->with(static::equalTo($key))
                  ->will(static::returnValue(false));

        $actual = $this->cache->getItem($key);

        static::assertNull($actual);
    }

    public function testGet()
    {
        $key = 'some:test:key';
        $expected = \uniqid(\time(), true);

        $this->adapter->expects(static::once())
                  ->method('get')
                  ->with(static::equalTo($key))
                  ->will(static::returnValue($expected));

        $this->serializer->expects(static::once())
                     ->method('unserialize')
                     ->with(static::equalTo($expected))
                     ->will(static::returnValue($expected));

        $actual = $this->cache->getItem($key);

        static::assertSame($expected, $actual);
    }

    public function testSet()
    {
        $key = 'some:test:key';
        $value = \uniqid(\time(), true);

        $this->serializer->expects(static::once())
                     ->method('serialize')
                     ->with(static::equalTo($value))
                     ->will(static::returnValue($value));

        $this->adapter->expects(static::once())
                  ->method('setExpired')
                  ->with(static::equalTo($key), static::equalTo($value));

        $this->cache->setItem($key, $value, 10);
    }

    public function testSetWithTtl()
    {
        $key = 'some:test:key';
        $value = \uniqid(\time(), true);
        $ttl = \random_int(20, 5000);

        $this->serializer->expects(static::once())
                     ->method('serialize')
                     ->with(static::equalTo($value))
                     ->will(static::returnValue($value));

        $this->adapter->expects(static::once())
                  ->method('setExpired')
                  ->with(static::equalTo($key), static::equalTo($value), static::equalTo($ttl));

        $this->cache->setItem($key, $value, $ttl);
    }

    public function testSetToDate()
    {
        $key = 'some:test:key';
        $value = \uniqid(\time(), true);
        $date = new DateTime();
        $time = $date->getTimestamp();
        $date->add(new DateInterval('PT1H'));

        $this->serializer->expects(static::once())
                     ->method('serialize')
                     ->with(static::equalTo($value))
                     ->will(static::returnValue($value));

        $this->adapter->expects(static::once())
                  ->method('setExpired')
                  ->with(static::equalTo($key), static::equalTo($value), static::equalTo($date->getTimestamp() - $time));

        $this->cache->setItemToDate($key, $value, $date);
    }

    public function testSetWrongDate()
    {
        $this->expectException(\Voku\Cache\Exception\InvalidArgumentException::class);

        $key = 'some:test:key';
        $value = \uniqid(\time(), true);
        $date = new DateTime();
        $date->sub(new DateInterval('PT1H'));

        $this->cache->setItemToDate($key, $value, $date);
    }

    public function testRemove()
    {
        $key = 'some:test:key';

        $this->adapter->expects(static::once())
                  ->method('remove')
                  ->with(static::equalTo($key));

        $this->cache->removeItem($key);
    }

    public function testExists()
    {
        $key = 'some:test:key';

        $this->adapter->expects(static::once())
                  ->method('exists')
                  ->with(static::equalTo($key));

        $this->cache->existsItem($key);
    }

    /**
     * @before
     */
    protected function setUpThanksForNothing()
    {
        $this->adapter = $this->createMock('Voku\Cache\AdapterApc');
        $this->serializer = $this->createMock('Voku\Cache\SerializerDefault');

        $this->cache = new Cache($this->adapter, $this->serializer, false, true);

        // reset default prefix
        $this->cache->setPrefix('');
    }
}

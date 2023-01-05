<?php

use Voku\Cache\CachePsr16;
use Voku\Cache\iAdapter;
use Voku\Cache\iSerializer;

/**
 * CachePsr16Test
 *
 * @internal
 */
final class CachePsr16Test extends \PHPUnit\Framework\TestCase
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

    protected $backupGlobalsBlacklist = [
        '_SESSION',
    ];

    public function testKeyPrefix()
    {
        $prefix = 'prefix:';

        $this->cache->setPrefix($prefix);
        $this->adapter->expects(static::once())
                      ->method('exists')
                      ->with(static::equalTo($prefix . 'lall'));

        $this->cache->get('lall');
    }

    public function testGetNotExists()
    {
        $key = 'some:test:key';

        $this->adapter->expects(static::once())
                      ->method('exists')
                      ->with(static::equalTo($key))
                      ->willReturn(false);

        $actual = $this->cache->get($key, null);

        static::assertNull($actual);
    }

    public function testGet()
    {
        $key = 'some:test:key';
        $expected = \uniqid(\time(), true);

        $this->cache->set($key, $expected);

        $this->adapter->expects(static::once())
                      ->method('exists')
                      ->with(static::equalTo($key))
                      ->willReturn(true);

        $this->adapter->expects(static::once())
                      ->method('get')
                      ->with(static::equalTo($key))
                      ->willReturn($expected);

        $this->serializer->expects(static::once())
                         ->method('unserialize')
                         ->with(static::equalTo($expected))
                         ->willReturn($expected);

        $actual = $this->cache->get($key);

        static::assertSame($expected, $actual);
    }

    public function testSetWithTtl()
    {
        $key = 'some:test:key';
        $value = \uniqid(\time(), true);
        $ttl = \random_int(20, 5000);

        $this->serializer->expects(static::once())
                         ->method('serialize')
                         ->with(static::equalTo($value))
                         ->willReturn($value);

        $this->adapter->expects(static::once())
                      ->method('setExpired')
                      ->with(static::equalTo($key), static::equalTo($value), static::equalTo($ttl));

        $this->cache->set($key, $value, $ttl);
    }

    public function testSetWithTtlDateInterval()
    {
        $key = 'some:test:key';
        $value = \uniqid(\time(), true);
        $ttl = new DateInterval('PT1H');

        $this->serializer->expects(static::once())
                         ->method('serialize')
                         ->with(static::equalTo($value))
                         ->willReturn($value);

        $this->adapter->expects(static::once())
                      ->method('setExpired')
                      ->with(static::equalTo($key), static::equalTo($value));

        $this->cache->set($key, $value, $ttl);
    }

    public function testRemove()
    {
        $key = 'some:test:key';

        $this->adapter->expects(static::once())
                      ->method('remove')
                      ->with(static::equalTo($key));

        $this->cache->delete($key);
    }

    public function testExists()
    {
        $key = 'some:test:key';

        $this->adapter->expects(static::once())
                      ->method('exists')
                      ->with(static::equalTo($key));

        $this->cache->has($key);
    }

    /**
     * @before
     */
    protected function setUpThanksForNothing()
    {
        if (\method_exists($this, 'createMock')) {
            $this->adapter = $this->createMock('Voku\Cache\AdapterArray');
            $this->serializer = $this->createMock('Voku\Cache\SerializerDefault');
        } else {
            $this->adapter = $this->createMock('Voku\Cache\AdapterArray');
            $this->serializer = $this->createMock('Voku\Cache\SerializerDefault');
        }

        $this->cache = new CachePsr16($this->adapter, $this->serializer, false, true);

        // reset default prefix
        $this->cache->setPrefix('');
    }
}

<?php

use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;

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
        $this->expectException(\voku\cache\Exception\InvalidArgumentException::class);

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

    public function testRemoveAll()
    {
        $this->adapter->expects(static::once())
                  ->method('removeAll')
                  ->willReturn(true);

        $result = $this->cache->removeAll();

        static::assertTrue($result);
    }

    public function testGetAdapter()
    {
        static::assertSame($this->adapter, $this->cache->getAdapter());
    }

    public function testGetSerializer()
    {
        static::assertSame($this->serializer, $this->cache->getSerializer());
    }

    public function testGetStaticCacheHitCounterDefault()
    {
        static::assertSame(10, $this->cache->getStaticCacheHitCounter());
    }

    public function testSetStaticCacheHitCounter()
    {
        $this->cache->setStaticCacheHitCounter(3);

        static::assertSame(3, $this->cache->getStaticCacheHitCounter());
    }

    public function testGetUsedAdapterClassName()
    {
        $className = $this->cache->getUsedAdapterClassName();

        // The mock wraps AdapterApc, so the class name contains it.
        static::assertStringContainsString('AdapterApc', $className);
    }

    public function testGetUsedSerializerClassName()
    {
        $className = $this->cache->getUsedSerializerClassName();

        // The mock wraps SerializerDefault, so the class name contains it.
        static::assertStringContainsString('SerializerDefault', $className);
    }

    public function testGetAndSetPrefix()
    {
        $this->cache->setPrefix('myprefix_');

        static::assertSame('myprefix_', $this->cache->getPrefix());
    }

    public function testUsedAdapterClassNameEmptyWhenNoAdapter()
    {
        // A disabled cache has no adapter.
        $cache = new Cache(null, null, false, false);

        static::assertSame('', $cache->getUsedAdapterClassName());
    }

    public function testUsedSerializerClassNameEmptyWhenNoSerializer()
    {
        $cache = new Cache(null, null, false, false);

        static::assertSame('', $cache->getUsedSerializerClassName());
    }

    public function testCacheIsNotReadyWhenDisabled()
    {
        $cache = new Cache(null, null, false, false);

        static::assertFalse($cache->getCacheIsReady());
    }

    public function testSetItemReturnsFalseWhenDisabled()
    {
        $cache = new Cache(null, null, false, false);

        static::assertFalse($cache->setItem('key', 'value'));
    }

    public function testGetItemReturnsNullWhenDisabled()
    {
        $cache = new Cache(null, null, false, false);

        static::assertNull($cache->getItem('key'));
    }

    public function testRemoveItemReturnsFalseWhenDisabled()
    {
        $cache = new Cache(null, null, false, false);

        static::assertFalse($cache->removeItem('key'));
    }

    public function testExistsItemReturnsFalseWhenDisabled()
    {
        $cache = new Cache(null, null, false, false);

        static::assertFalse($cache->existsItem('key'));
    }

    public function testRemoveAllReturnsFalseWhenDisabled()
    {
        $cache = new Cache(null, null, false, false);

        static::assertFalse($cache->removeAll());
    }

    public function testRemoveItemsReturnsFalseWhenDisabled()
    {
        $cache = new Cache(null, null, false, false);

        static::assertFalse($cache->removeItems('/^foo/'));
    }

    /**
     * @before
     */
    protected function setUpThanksForNothing()
    {
        $this->adapter = $this->createMock('voku\cache\AdapterApc');
        $this->serializer = $this->createMock('voku\cache\SerializerDefault');

        $this->cache = new Cache($this->adapter, $this->serializer, false, true);

        // reset default prefix
        $this->cache->setPrefix('');
    }
}

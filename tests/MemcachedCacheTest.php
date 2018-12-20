<?php

use voku\cache\AdapterMemcached;
use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;
use voku\cache\SerializerDefault;

/**
 * MemcachedCacheTest
 *
 * @internal
 */
final class MemcachedCacheTest extends \PHPUnit\Framework\TestCase
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

    protected $backupGlobalsBlacklist = [
        '_SESSION',
    ];

    public function testSetItem()
    {
        $return = $this->cache->setItem('foo', [1, 2, 3, 4]);

        static::assertTrue($return);
    }

    public function testGetItem()
    {
        $return = $this->cache->getItem('foo');

        static::assertSame([1, 2, 3, 4], $return);
    }

    public function testExistsItem()
    {
        $return = $this->cache->existsItem('foo');

        static::assertTrue($return);
    }

    public function testSetEmptyItem()
    {
        $return = $this->cache->setItem('foo_empty', '');

        static::assertTrue($return);
    }

    public function testGetEmptyItem()
    {
        $return = $this->cache->getItem('foo_empty');

        static::assertSame('', $return);
    }

    public function testExistsEmptyItem()
    {
        $return = $this->cache->existsItem('foo_empty');

        static::assertTrue($return);
    }

    public function testGetCacheIsReady()
    {
        $return = $this->cache->getCacheIsReady();

        static::assertTrue($return);
    }

    public function testSetGetItemWithPrefix()
    {
        $this->cache->setPrefix('bar');
        $prefix = $this->cache->getPrefix();
        static::assertSame('bar', $prefix);

        $return = $this->cache->setItem('foo', [3, 2, 1]);
        static::assertTrue($return);

        $return = $this->cache->getItem('foo');
        static::assertSame([3, 2, 1], $return);
    }

    public function testSetGetCacheWithEndDateTime()
    {
        $expireDate = new DateTime();
        $interval = DateInterval::createFromDateString('+3 seconds');
        $expireDate->add($interval);

        $return = $this->cache->setItemToDate('testSetGetCacheWithEndDateTime', [3, 2, 1], $expireDate);
        static::assertTrue($return);

        $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
        static::assertSame([3, 2, 1], $return);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $memcached = null;
        $isMemcachedAvailable = false;
        if (\extension_loaded('memcached')) {
            $memcached = new \Memcached();
            $isMemcachedAvailable = $memcached->addServer('127.0.0.1', '11211');
        }

        if ($isMemcachedAvailable === false) {
            $memcached = null;
        }

        $this->adapter = new AdapterMemcached($memcached);
        $this->serializer = new SerializerDefault();

        if ($this->adapter->installed() === false) {
            static::markTestSkipped(
          'The Memcached extension is not available.'
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

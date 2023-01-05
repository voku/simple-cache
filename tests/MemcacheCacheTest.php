<?php

use Voku\Cache\AdapterMemcache;
use Voku\Cache\Cache;
use Voku\Cache\iAdapter;
use Voku\Cache\iSerializer;
use Voku\Cache\SerializerNo;

/**
 * MemcacheCacheTest
 *
 * @internal
 */
final class MemcacheCacheTest extends \PHPUnit\Framework\TestCase
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

    public function testGetUsedAdapterClassName()
    {
        static::assertSame('Voku\Cache\AdapterMemcache', $this->cache->getUsedAdapterClassName());
    }

    public function testGetUsedSerializerClassName()
    {
        static::assertSame('Voku\Cache\SerializerNo', $this->cache->getUsedSerializerClassName());
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
     * @before
     */
    protected function setUpThanksForNothing()
    {
        $memcache = null;
        $isMemcacheAvailable = false;
        if (\class_exists('\Memcache')) {
            $memcache = new \Memcache();
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            $isMemcacheAvailable = @$memcache->connect('127.0.0.1', 11211);
        }

        if ($isMemcacheAvailable === false) {
            $memcache = null;
        }

        $this->adapter = new AdapterMemcache($memcache);
        $this->serializer = new SerializerNo();

        if ($this->adapter->installed() === false) {
            static::markTestSkipped('The Memcache extension is not available.');
        }

        $this->cache = new Cache($this->adapter, $this->serializer, false, true);

        // reset default prefix
        $this->cache->setPrefix('');
    }
}

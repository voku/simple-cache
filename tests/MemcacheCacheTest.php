<?php

use voku\cache\AdapterMemcache;
use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;
use voku\cache\SerializerNo;

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
        static::assertSame('voku\cache\AdapterMemcache', $this->cache->getUsedAdapterClassName());
    }

    public function testGetUsedSerializerClassName()
    {
        static::assertSame('voku\cache\SerializerNo', $this->cache->getUsedSerializerClassName());
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

    public function testAdapterGetAllKeys()
    {
        // setUp already marks this skipped when Memcache is unavailable.
        assert($this->adapter instanceof \voku\cache\AdapterMemcache);

        $this->adapter->removeAll();

        // Empty at start.
        static::assertSame([], $this->adapter->getAllKeys());

        // Keys appear after set().
        $this->adapter->set('fruit1', 'apple');
        $this->adapter->set('fruit2', 'banana');

        $keys = $this->adapter->getAllKeys();
        \sort($keys);
        static::assertSame(['fruit1', 'fruit2'], $keys);

        // Key disappears after remove().
        $this->adapter->remove('fruit1');
        $keys = $this->adapter->getAllKeys();
        static::assertNotContains('fruit1', $keys);
        static::assertContains('fruit2', $keys);

        // All keys gone after removeAll().
        $this->adapter->removeAll();
        static::assertSame([], $this->adapter->getAllKeys());
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

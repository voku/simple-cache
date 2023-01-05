<?php

use Voku\Cache\Cache;
use Voku\Cache\iAdapter;
use Voku\Cache\iSerializer;

/**
 * @internal
 */
final class CacheAutoInitOverwriteTest extends \PHPUnit\Framework\TestCase
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

        $item = $this->cache->getItem('lall');

        static::assertNull($item);
    }

    public function testGetNotExists()
    {
        $key = 'some:test:key';

        $actual = $this->cache->getItem($key);

        static::assertNull($actual);
    }

    public function testSet()
    {
        $key = 'some:test:key';
        $value = \uniqid(\time(), true);

        $result = $this->cache->setItem($key, $value, 10);

        static::assertTrue($result);
    }

    /**
     * @depends testSet
     */
    public function testKeyAfterSet()
    {
        $item = $this->cache->getItem('some:test:key');

        static::assertNotNull($item);
    }

    public function testSetWithTtl()
    {
        $key = 'some:test:key';
        $value = \uniqid(\time(), true);
        $ttl = \random_int(20, 5000);

        $result = $this->cache->setItem($key, $value, $ttl);

        static::assertTrue($result);
    }

    public function testSetToDate()
    {
        $key = 'some:test:key';
        $value = \uniqid(\time(), true);
        $date = (new DateTime('now'))->add(new DateInterval('PT1H'));

        $result = $this->cache->setItemToDate($key, $value, $date);

        static::assertTrue($result);
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

        $result = $this->cache->removeItem($key);

        static::assertTrue($result);
    }

    /**
     * @depends testRemove
     */
    public function testExists()
    {
        $key = 'some:test:key';

        $result = $this->cache->existsItem($key);

        static::assertFalse($result);
    }

    /**
     * @before
     */
    protected function setUpThanksForNothing()
    {
        $cacheManager = new \Voku\Cache\CacheAdapterAutoManager();
        /** @noinspection PhpUnhandledExceptionInspection */
        $cacheManager->addAdapter(
            \Voku\Cache\AdapterOpCache::class,
            static function () {
                return \realpath(\sys_get_temp_dir()) . '/simple_php_cache_v2';
            }
        );
        /** @noinspection PhpUnhandledExceptionInspection */
        $cacheManager->addAdapter(
            \Voku\Cache\AdapterArray::class
        );

        $this->cache = new Cache(
            null,
            null,
            false,
            true,
            false,
            false,
            false,
            false,
            '',
            $cacheManager,
            true
        );

        // reset default prefix
        $this->cache->setPrefix('');
    }
}

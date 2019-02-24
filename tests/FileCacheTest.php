<?php

use voku\cache\AdapterFile;
use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;
use voku\cache\SerializerDefault;

/**
 * FileCacheTest
 *
 * @internal
 */
final class FileCacheTest extends \PHPUnit\Framework\TestCase
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
        $return = $this->cache->setItem('2571_519ä#-`9de.foo::bar', [1, 2, 3]);
        static::assertTrue($return);

        $return = $this->cache->setItem('2571_519ä#-`9de.foo::bar', [1, 2]);
        static::assertTrue($return);

        $return = $this->cache->setItem('2571_519ä#-`9de.foo::bar', [1, 2, 3, 4]);
        static::assertTrue($return);

        $return = $this->cache->setItem('object-test-€€€', [1, 2, 3, 4]);
        static::assertTrue($return);
    }

    /**
     * @depends  testSetItem
     */
    public function testGetItem()
    {
        for ($i = 0; $i <= 20; $i++) {
            $return = $this->cache->getItem('2571_519ä#-`9de.foo::bar');
            static::assertSame([1, 2, 3, 4], $return);
        }

        $return = $this->cache->getItem('object-test-€€€');
        static::assertSame([1, 2, 3, 4], $return);
    }

    /**
     * @depends  testSetItem
     */
    public function testExistsItem()
    {
        $return = $this->cache->existsItem('2571_519ä#-`9de.foo::bar');

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

        $return = $this->cache->setItemToDate(
            'testSetGetCacheWithEndDateTime',
            [3, 2, 1],
            $expireDate
        );
        static::assertTrue($return);

        $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
        static::assertSame([3, 2, 1], $return);
    }

    public function testRemove()
    {
        $return = $this->cache->setItem('foobar_test', [4, 2, 1]);
        static::assertTrue($return);

        $return = $this->cache->setItem('foobar_test_v2', [5, 2, 1]);
        static::assertTrue($return);

        $return = $this->cache->setItem('foobar_test_v3', [6, 2, 1]);
        static::assertTrue($return);

        $return = $this->cache->getItem('foobar_test');
        static::assertSame([4, 2, 1], $return);

        $return = $this->cache->getItem('foobar_test_v2');
        static::assertSame([5, 2, 1], $return);

        $return = $this->cache->getItem('foobar_test_v3');
        static::assertSame([6, 2, 1], $return);

        // -- remove one item

        $return = $this->cache->removeItem('foobar_test');
        static::assertTrue($return);

        // -- remove one item - test

        $return = $this->cache->getItem('foobar_test');
        static::assertNull($return);

        $return = $this->cache->getItem('foobar_test_v2');
        static::assertSame([5, 2, 1], $return);

        // -- remove all

        $return = $this->cache->removeAll();
        static::assertTrue($return);

        // -- remove all - tests

        $return = $this->cache->getItem('foobar_test');
        static::assertNull($return);

        $return = $this->cache->getItem('foobar_test_v2');
        static::assertNull($return);

        $return = $this->cache->getItem('foobar_test_v3');
        static::assertNull($return);
    }

    public function testSetGetCacheWithEndDateTimeAndStaticCacheAuto()
    {
        $expireDate = new DateTime();
        $interval = DateInterval::createFromDateString('+1 seconds');
        $expireDate->add($interval);

        $return = $this->cache->setItemToDate(
            'testSetGetCacheWithEndDateTime',
            [3, 2, 1],
            $expireDate
        );
        static::assertTrue($return);

        for ($i = 0; $i <= 20; $i++) {
            $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
            static::assertSame([3, 2, 1], $return);
        }

        \sleep(2);

        $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
        static::assertNull($return);
    }

    public function testSetGetCacheWithEndDateTimeAndStaticCacheForce()
    {
        $expireDate = new DateTime();
        $interval = DateInterval::createFromDateString('+1 seconds');
        $expireDate->add($interval);

        $return = $this->cache->setItemToDate(
            'testSetGetCacheWithEndDateTime',
            [3, 2, 1],
            $expireDate
        );
        static::assertTrue($return);

        for ($i = 0; $i <= 4; $i++) {
            $return = $this->cache->getItem('testSetGetCacheWithEndDateTime', 2);
            static::assertSame([3, 2, 1], $return);
        }

        \sleep(2);

        $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
        static::assertNull($return);
    }

    public function testGetUsedAdapterClassName()
    {
        static::assertSame('voku\cache\AdapterFile', $this->cache->getUsedAdapterClassName());
    }

    public function testGetUsedSerializerClassName()
    {
        static::assertSame('voku\cache\SerializerDefault', $this->cache->getUsedSerializerClassName());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->adapter = new AdapterFile();
        $this->serializer = new SerializerDefault();

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

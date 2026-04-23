<?php

use voku\cache\Cache;

/**
 * @internal
 */
final class CacheChainTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \voku\cache\CacheChain
     */
    private $cache;

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

    public function testGetCaches()
    {
        $caches = $this->cache->getCaches();

        static::assertIsArray($caches);
        // setUp adds cacheApc (prepend via constructor) then cacheArray (prepend via addCache),
        // so there are 2 entries in the chain.
        static::assertCount(2, $caches);
    }

    public function testAddCacheWithAppendPutsItLast()
    {
        $extraCache = new Cache(
            new \voku\cache\AdapterArray(),
            new \voku\cache\SerializerDefault(),
            false,
            true
        );
        $extraCache->setPrefix('chain_append_test_');

        $before = \count($this->cache->getCaches());

        $this->cache->addCache($extraCache, false); // false = append, not prepend

        $after = $this->cache->getCaches();

        static::assertCount($before + 1, $after);
        static::assertSame($extraCache, \end($after));
    }

    public function testRemoveItems()
    {
        $this->cache->removeAll();

        $this->cache->setItem('chain_pattern_a', [10]);
        $this->cache->setItem('chain_pattern_b', [20]);
        $this->cache->setItem('chain_keep', [30]);

        $result = $this->cache->removeItems('/^chain_pattern_/');
        static::assertTrue($result);

        static::assertNull($this->cache->getItem('chain_pattern_a'));
        static::assertNull($this->cache->getItem('chain_pattern_b'));
        static::assertSame([30], $this->cache->getItem('chain_keep'));
    }

    public function testSetGetItemWithPrefix()
    {
        $this->cache->setPrefix('bar');

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
            $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
            static::assertSame([3, 2, 1], $return);
        }

        \sleep(2);

        $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
        static::assertNull($return);
    }

    /**
     * @before
     */
    protected function setUpThanksForNothing()
    {
        $cacheApc = new Cache(
            new \voku\cache\AdapterApcu(),
            new \voku\cache\SerializerIgbinary(),
            false,
            true
        );
        // reset default prefix
        $cacheApc->setPrefix('');

        $cacheArray = new Cache(
            new \voku\cache\AdapterArray(),
            new \voku\cache\SerializerIgbinary(),
            false,
            true
        );
        // reset default prefix
        $cacheArray->setPrefix('');

        $this->cache = new \voku\cache\CacheChain([$cacheApc]);
        $this->cache->addCache($cacheArray);
    }
}

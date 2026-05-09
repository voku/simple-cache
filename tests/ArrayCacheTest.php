<?php

use voku\cache\AdapterArray;
use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;
use voku\cache\SerializerDefault;

/**
 * ArrayCacheTest
 *
 * @internal
 */
final class ArrayCacheTest extends \PHPUnit\Framework\TestCase
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

    public function testSetItemOfNull()
    {
        $return = $this->cache->setItem('foo_null', null);

        static::assertTrue($return);

        // -----

        $return = $this->cache->getItem('foo_null');
        static::assertNull($return);
    }

    public function testSetItem()
    {
        $return = $this->cache->setItem('foo', [1, 2, 3, 4]);
        static::assertTrue($return);

        $return = $this->cache->getItem('foo');
        static::assertSame([1, 2, 3, 4], $return);

        // -----

        $ao = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);

        $ao['prop'] = 'prop data';
        $ao['arr'] = 'array data';

        $return = $this->cache->setItem('ao', $ao);

        static::assertTrue($return);
    }

    public function testGetItem()
    {
        $return = $this->cache->getItem('foo');

        static::assertSame([1, 2, 3, 4], $return);

        // -----

        $return = $this->cache->getItem('ao');

        $ao = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);

        $ao['prop'] = 'prop data';
        $ao['arr'] = 'array data';

        static::assertSame($ao->prop, $return->prop);
        static::assertSame($ao['arr'], $return['arr']);
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

    public function testGetStaticValues()
    {
        $return = $this->cache->setItem('foo', [3, 2, 1]);
        static::assertTrue($return);

        $return = $this->cache->getItem('foo');
        static::assertSame([3, 2, 1], $return);

        assert($this->cache->getAdapter() instanceof AdapterArray);
        $values = $this->cache->getAdapter()->getStaticValues();
        $expectedAo = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        $expectedAo['prop'] = 'prop data';
        $expectedAo['arr'] = 'array data';

        // User-set values are present with the expected serialized content.
        static::assertSame('N;', $values['foo_null']);
        static::assertSame('a:3:{i:0;i:3;i:1;i:2;i:2;i:1;}', $values['foo']);
        static::assertSame(\serialize($expectedAo), $values['ao']);
        static::assertSame('a:3:{i:0;i:3;i:1;i:2;i:2;i:1;}', $values['barfoo']);

        // The adapter also holds key-registry entries (one per prefix used so far).
        static::assertArrayHasKey('__simple_cache_keys_index__', $values);
        static::assertArrayHasKey('bar__simple_cache_keys_index__', $values);
    }

    public function testGetStaticKeys()
    {
        $return = $this->cache->setItem('foo', [3, 2, 1]);
        static::assertTrue($return);

        $return = $this->cache->getItem('foo');
        static::assertSame([3, 2, 1], $return);

        assert($this->cache->getAdapter() instanceof AdapterArray);
        $keys = $this->cache->getAdapter()->getStaticKeys();

        // All user-set keys are present.
        static::assertContains('foo_null', $keys);
        static::assertContains('foo', $keys);
        static::assertContains('ao', $keys);
        static::assertContains('barfoo', $keys);

        // The adapter also holds key-registry entries (one per prefix used so far).
        static::assertContains('__simple_cache_keys_index__', $keys);
        static::assertContains('bar__simple_cache_keys_index__', $keys);
    }

    public function testRemoveItems()
    {
        $return = $this->cache->setItem('imagecache_foo', [1, 2, 3]);
        static::assertTrue($return);

        $return = $this->cache->setItem('imagecache_bar', [4, 5, 6]);
        static::assertTrue($return);

        $return = $this->cache->setItem('other_item', [7, 8, 9]);
        static::assertTrue($return);

        // -- verify items exist

        static::assertSame([1, 2, 3], $this->cache->getItem('imagecache_foo'));
        static::assertSame([4, 5, 6], $this->cache->getItem('imagecache_bar'));
        static::assertSame([7, 8, 9], $this->cache->getItem('other_item'));

        // -- remove items matching pattern

        $return = $this->cache->removeItems('/^imagecache_/');
        static::assertTrue($return);

        // -- verify matching items are removed

        static::assertNull($this->cache->getItem('imagecache_foo'));
        static::assertNull($this->cache->getItem('imagecache_bar'));

        // -- verify non-matching item is still present

        static::assertSame([7, 8, 9], $this->cache->getItem('other_item'));
    }

    public function testRemoveItemsWithPrefix()
    {
        $this->cache->setPrefix('myapp_');

        $return = $this->cache->setItem('imagecache_foo', [1, 2, 3]);
        static::assertTrue($return);

        $return = $this->cache->setItem('imagecache_bar', [4, 5, 6]);
        static::assertTrue($return);

        $return = $this->cache->setItem('other_item', [7, 8, 9]);
        static::assertTrue($return);

        // -- remove items matching pattern (pattern matches raw key, not prefixed store key)

        $return = $this->cache->removeItems('/^imagecache_/');
        static::assertTrue($return);

        // -- verify matching items are removed

        static::assertNull($this->cache->getItem('imagecache_foo'));
        static::assertNull($this->cache->getItem('imagecache_bar'));

        // -- verify non-matching item is still present

        static::assertSame([7, 8, 9], $this->cache->getItem('other_item'));
    }

    public function testRemoveItemsNoMatch()
    {
        $return = $this->cache->setItem('foo', [1, 2, 3]);
        static::assertTrue($return);

        // -- no items match, should return true

        $return = $this->cache->removeItems('/^nonexistent_/');
        static::assertTrue($return);

        // -- item is still present

        static::assertSame([1, 2, 3], $this->cache->getItem('foo'));
    }

    public function testSetGetCacheWithEndDateTime()
    {
        $expireDate = new DateTime();
        $interval = DateInterval::createFromDateString('+1 seconds');
        $expireDate->add($interval);

        $return = $this->cache->setItemToDate('testSetGetCacheWithEndDateTime', [3, 2, 1], $expireDate);
        static::assertTrue($return);

        $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
        static::assertSame([3, 2, 1], $return);

        \sleep(2);

        $return = $this->cache->getItem('testSetGetCacheWithEndDateTime');
        static::assertNull($return);
    }

    /**
     * @before
     */
    protected function setUpThanksForNothing()
    {
        $this->adapter = new AdapterArray();
        $this->serializer = new SerializerDefault();

        $this->cache = new Cache($this->adapter, $this->serializer, false, true);

        // reset default prefix
        $this->cache->setPrefix('');
    }
}

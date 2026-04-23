<?php

use voku\cache\AdapterArray;
use voku\cache\CachePsr16;
use voku\cache\SerializerDefault;

/**
 * Functional tests for CachePsr16 using a real in-memory adapter.
 * Covers setMultiple / getMultiple / deleteMultiple / clear and default-value behaviour.
 *
 * @internal
 */
final class CachePsr16ArrayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CachePsr16
     */
    private $cache;

    protected $backupGlobalsBlacklist = [
        '_SESSION',
    ];

    // -------------------------------------------------------------------------
    // set / get / has with defaults
    // -------------------------------------------------------------------------

    public function testSetAndGetRoundtrip()
    {
        $result = $this->cache->set('hello', 'world');
        static::assertTrue($result);

        static::assertTrue($this->cache->has('hello'));
        static::assertSame('world', $this->cache->get('hello'));
    }

    public function testGetWithDefaultWhenMissing()
    {
        $result = $this->cache->get('nonexistent_key', 'fallback');

        static::assertSame('fallback', $result);
    }

    public function testGetReturnsNullDefaultWhenMissing()
    {
        static::assertNull($this->cache->get('still_missing'));
    }

    // -------------------------------------------------------------------------
    // setMultiple / getMultiple
    // -------------------------------------------------------------------------

    public function testSetMultipleAndGetMultiple()
    {
        $result = $this->cache->setMultiple(['alpha' => 1, 'beta' => 2, 'gamma' => 3]);
        static::assertTrue($result);

        $items = $this->cache->getMultiple(['alpha', 'beta', 'gamma']);

        static::assertSame(1, $items['alpha']);
        static::assertSame(2, $items['beta']);
        static::assertSame(3, $items['gamma']);
    }

    public function testGetMultipleWithDefaultForMissingKeys()
    {
        $this->cache->set('exists', 'yes');

        $items = $this->cache->getMultiple(['exists', 'missing_one', 'missing_two'], 'N/A');

        static::assertSame('yes', $items['exists']);
        static::assertSame('N/A', $items['missing_one']);
        static::assertSame('N/A', $items['missing_two']);
    }

    // -------------------------------------------------------------------------
    // deleteMultiple
    // -------------------------------------------------------------------------

    public function testDeleteMultipleRemovesMatchingKeysOnly()
    {
        $this->cache->setMultiple(['del1' => 'a', 'del2' => 'b', 'keep' => 'c']);

        $result = $this->cache->deleteMultiple(['del1', 'del2']);
        static::assertTrue($result);

        static::assertNull($this->cache->get('del1'));
        static::assertNull($this->cache->get('del2'));
        static::assertSame('c', $this->cache->get('keep'));
    }

    public function testDeleteMultipleWithEmptyListReturnsTrue()
    {
        $result = $this->cache->deleteMultiple([]);

        static::assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // clear
    // -------------------------------------------------------------------------

    public function testClearRemovesAllItems()
    {
        $this->cache->set('k1', 'v1');
        $this->cache->set('k2', 'v2');

        $result = $this->cache->clear();
        static::assertTrue($result);

        static::assertFalse($this->cache->has('k1'));
        static::assertFalse($this->cache->has('k2'));
    }

    // -------------------------------------------------------------------------
    // delete (single)
    // -------------------------------------------------------------------------

    public function testDeleteRemovesItem()
    {
        $this->cache->set('bye', 'soon');
        static::assertTrue($this->cache->has('bye'));

        $result = $this->cache->delete('bye');
        static::assertTrue($result);

        static::assertFalse($this->cache->has('bye'));
    }

    // -------------------------------------------------------------------------
    // setUp
    // -------------------------------------------------------------------------

    /**
     * @before
     */
    protected function setUpThanksForNothing()
    {
        $this->cache = new CachePsr16(new AdapterArray(), new SerializerDefault(), false, true);

        // reset default prefix and wipe adapter state from previous tests
        $this->cache->setPrefix('psr16array_test_');
        $this->cache->removeAll();
        $this->cache->setPrefix('psr16array_test_');
    }
}

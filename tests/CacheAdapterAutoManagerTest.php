<?php

use voku\cache\AdapterArray;
use voku\cache\AdapterFile;
use voku\cache\CacheAdapterAutoManager;
use voku\cache\Exception\InvalidArgumentException;

/**
 * Tests for CacheAdapterAutoManager – validates adapters, callables, and merge behaviour.
 *
 * @internal
 */
final class CacheAdapterAutoManagerTest extends \PHPUnit\Framework\TestCase
{
    // -------------------------------------------------------------------------
    // addAdapter validation
    // -------------------------------------------------------------------------

    public function testAddAdapterThrowsForClassThatDoesNotImplementIAdapter()
    {
        $this->expectException(InvalidArgumentException::class);

        $manager = new CacheAdapterAutoManager();
        $manager->addAdapter(\stdClass::class);
    }

    public function testAddAdapterReturnsFluentInterface()
    {
        $manager = new CacheAdapterAutoManager();
        $result = $manager->addAdapter(AdapterArray::class);

        static::assertSame($manager, $result);
    }

    // -------------------------------------------------------------------------
    // getAdapters / getDefaultsForAutoInit
    // -------------------------------------------------------------------------

    public function testGetAdaptersYieldsRegisteredAdapter()
    {
        $manager = new CacheAdapterAutoManager();
        $manager->addAdapter(AdapterArray::class);

        $found = [];
        foreach ($manager->getAdapters() as $class => $callable) {
            $found[$class] = $callable;
        }

        static::assertArrayHasKey(AdapterArray::class, $found);
        static::assertNull($found[AdapterArray::class]);
    }

    public function testGetAdaptersYieldsCallableWhenProvided()
    {
        $callable = static function () { return null; };

        $manager = new CacheAdapterAutoManager();
        $manager->addAdapter(AdapterArray::class, $callable);

        foreach ($manager->getAdapters() as $class => $fn) {
            if ($class === AdapterArray::class) {
                static::assertSame($callable, $fn);
            }
        }
    }

    public function testGetDefaultsForAutoInitContainsAdapterArray()
    {
        $manager = CacheAdapterAutoManager::getDefaultsForAutoInit();

        $classes = [];
        foreach ($manager->getAdapters() as $class => $callable) {
            $classes[] = $class;
        }

        static::assertContains(AdapterArray::class, $classes);
        static::assertGreaterThan(1, \count($classes));
    }

    // -------------------------------------------------------------------------
    // merge
    // -------------------------------------------------------------------------

    public function testMergeAddsAdapterFromOtherManager()
    {
        $manager1 = new CacheAdapterAutoManager();
        $manager1->addAdapter(AdapterArray::class);

        $manager2 = new CacheAdapterAutoManager();
        $manager2->addAdapter(AdapterFile::class);

        $manager1->merge($manager2);

        $classes = [];
        foreach ($manager1->getAdapters() as $class => $callable) {
            $classes[] = $class;
        }

        static::assertContains(AdapterArray::class, $classes);
        static::assertContains(AdapterFile::class, $classes);
    }

    public function testMergeUpdatesCallableForExistingAdapterAtNonZeroIndex()
    {
        // Put AdapterFile at index 0 and AdapterArray at index 1.
        // array_search will return 1 (truthy) for AdapterArray, triggering the
        // update branch inside merge().
        $manager1 = new CacheAdapterAutoManager();
        $manager1->addAdapter(AdapterFile::class);
        $manager1->addAdapter(AdapterArray::class);

        $callable = static function () { return null; };

        $manager2 = new CacheAdapterAutoManager();
        $manager2->addAdapter(AdapterArray::class, $callable);

        $manager1->merge($manager2);

        // The update branch must not duplicate AdapterArray – still 2 adapters.
        $classes = [];
        foreach ($manager1->getAdapters() as $class => $fn) {
            $classes[] = $class;
        }

        static::assertCount(2, $classes);
        static::assertSame([AdapterFile::class, AdapterArray::class], $classes);
    }

    public function testMergeReturnsSelf()
    {
        $manager1 = new CacheAdapterAutoManager();
        $manager1->addAdapter(AdapterArray::class);

        $manager2 = new CacheAdapterAutoManager();
        $manager2->addAdapter(AdapterFile::class);

        $result = $manager1->merge($manager2);

        static::assertSame($manager1, $result);
    }
}

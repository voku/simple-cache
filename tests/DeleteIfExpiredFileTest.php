<?php

use voku\cache\AdapterFile;
use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;
use voku\cache\SerializerDefault;

/**
 * DeleteIfExpiredFileTest
 *
 * Test the new deleteIfExpired parameter in get() method with file adapter
 *
 * @internal
 */
final class DeleteIfExpiredFileTest extends \PHPUnit\Framework\TestCase
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

    public function testGetWithDeleteIfExpiredTrue()
    {
        // Set an item with 1 second TTL
        $return = $this->cache->setItem('test_key_file', 'test_value', 1);
        static::assertTrue($return);

        // Get item immediately - should exist
        $return = $this->cache->getItem('test_key_file');
        static::assertSame('test_value', $return);

        // Get the actual store key that Cache uses (it's hashed)
        $reflection = new \ReflectionClass($this->cache);
        $method = $reflection->getMethod('calculateStoreKey');
        $method->setAccessible(true);
        $storeKey = $method->invoke($this->cache, 'test_key_file');

        // Wait for expiration
        \sleep(2);

        // Get with deleteIfExpired=true (default) - should return null and delete the item
        $return = $this->adapter->get($storeKey, true);
        static::assertNull($return);

        // Verify the item was deleted (file should not exist)
        $return = $this->adapter->exists($storeKey);
        static::assertFalse($return);
    }

    public function testGetWithDeleteIfExpiredFalse()
    {
        // Set an item with 1 second TTL
        $return = $this->cache->setItem('test_key_file2', 'test_value2', 1);
        static::assertTrue($return);

        // Get item immediately - should exist
        $return = $this->cache->getItem('test_key_file2');
        static::assertSame('test_value2', $return);

        // Get the actual store key that Cache uses (it's hashed)
        $reflection = new \ReflectionClass($this->cache);
        $method = $reflection->getMethod('calculateStoreKey');
        $method->setAccessible(true);
        $storeKey = $method->invoke($this->cache, 'test_key_file2');

        // Wait for expiration
        \sleep(2);

        // Get with deleteIfExpired=false - should return null but NOT delete the item
        // Use the store key, not the original key
        $return = $this->adapter->get($storeKey, false);
        static::assertNull($return);

        // With file adapter, we can check if the file still exists
        assert($this->adapter instanceof AdapterFile);
        // Use reflection to get the file name
        $reflection = new \ReflectionClass($this->adapter);
        $method = $reflection->getMethod('getFileName');
        $method->setAccessible(true);
        $fileName = $method->invoke($this->adapter, $storeKey);
        
        static::assertTrue(\file_exists($fileName), 'File should still exist when deleteIfExpired=false');
        
        // Now call get with deleteIfExpired=true (default) and verify it gets deleted
        $return = $this->adapter->get($storeKey, true);
        static::assertNull($return);
        static::assertFalse(\file_exists($fileName), 'File should be deleted when deleteIfExpired=true');
    }

    public function testGetWithDefaultBehavior()
    {
        // Set an item with 1 second TTL
        $return = $this->cache->setItem('test_key_file3', 'test_value3', 1);
        static::assertTrue($return);

        // Get the actual store key that Cache uses (it's hashed)
        $reflection = new \ReflectionClass($this->cache);
        $method = $reflection->getMethod('calculateStoreKey');
        $method->setAccessible(true);
        $storeKey = $method->invoke($this->cache, 'test_key_file3');

        // Wait for expiration
        \sleep(2);

        // Get without specifying deleteIfExpired - should use default (true) and delete
        $return = $this->adapter->get($storeKey);
        static::assertNull($return);

        // Verify the item was deleted (default behavior)
        $return = $this->adapter->exists($storeKey);
        static::assertFalse($return);
    }

    /**
     * @before
     */
    protected function setUpThanksForNothing()
    {
        $cacheDir = \sys_get_temp_dir() . '/simple_php_cache_test_delete_if_expired';
        
        $this->adapter = new AdapterFile($cacheDir);
        $this->serializer = new SerializerDefault();

        $this->cache = new Cache($this->adapter, $this->serializer, false, true);

        // reset default prefix
        $this->cache->setPrefix('');
        
        // Clear all cache to ensure clean state
        $this->adapter->removeAll();
    }
}

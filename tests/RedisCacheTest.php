<?php

use voku\cache\AdapterPredis;
use voku\cache\Cache;
use voku\cache\iAdapter;
use voku\cache\iSerializer;
use voku\cache\SerializerDefault;

/**
 * RedisCacheTest
 *
 * @internal
 */
final class RedisCacheTest extends \PHPUnit\Framework\TestCase
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
        $redis = null;
        $isRedisAvailable = false;
        if (
        \extension_loaded('redis')
        &&
        \class_exists('\Predis\Client')
    ) {
            /** @noinspection PhpUndefinedNamespaceInspection */
            $redis = new \Predis\Client(
          [
              'scheme'  => 'tcp',
              'host'    => '127.0.0.1',
              'port'    => 6379,
              'timeout' => '2.0',
          ]
      );

            try {
                $redis->connect();
                $isRedisAvailable = $redis->getConnection()->isConnected();
            } catch (\Exception $e) {
                // nothing
            }
        }

        if ($isRedisAvailable === false) {
            $redis = null;
        }

        $this->adapter = new AdapterPredis($redis);
        $this->serializer = new SerializerDefault();

        if ($this->adapter->installed() === false) {
            static::markTestSkipped(
          'Redis is not available.'
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

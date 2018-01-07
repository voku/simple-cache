<?php

declare(strict_types=1);

namespace voku\cache;

/**
 * AdapterOpCache: PHP-OPcache
 *
 * OPcache improves PHP performance by storing precompiled script bytecode
 * in shared memory, thereby removing the need for PHP to load and
 * parse scripts on each request.
 *
 * @package voku\cache
 */
class AdapterOpCache extends AdapterFile
{
  /**
   * {@inheritdoc}
   */
  public function __construct($cacheDir = null)
  {
    parent::__construct($cacheDir);

    $this->serializer = new SerializerNo();
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $key)
  {
    $path = $this->getFileName($key);

    if (
        \file_exists($path) === false
        ||
        \filesize($path) === 0
    ) {
      return null;
    }

    /** @noinspection PhpIncludeInspection */
    $data = include $path;

    if (!$data || !$this->validateDataFromCache($data)) {
      return null;
    }

    if ($this->ttlHasExpired($data['ttl']) === true) {
      $this->remove($key);

      return null;
    }

    return $data['value'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileName(string $key): string
  {
    $result = $this->cacheDir . DIRECTORY_SEPARATOR . self::CACHE_FILE_PREFIX . $key . '.php';

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpired(string $key, $value, int $ttl = 0): bool
  {
    $item = [
        'value' => $value,
        'ttl'   => $ttl ? $ttl + \time() : 0,
    ];
    $content = \var_export($item, true);

    $content = '<?php
    
    static $data = [
      0 => ' . $content . ',
    ];
    
    $result =& $data;
    unset($data);
    return $result[0];';

    return (bool)\file_put_contents($this->getFileName($key), $content);
  }
}

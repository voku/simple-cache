<?php

namespace voku\cache;

/**
 * AdapterFile: File-adapter
 *
 * @package   voku\cache
 */
class AdapterFile implements iAdapter
{
  const CACHE_FILE_PREFIX = '__';
  const CACHE_FILE_SUBFIX = '.php.cache';

  /**
   * @var bool
   */
  public $installed = false;

  /**
   * @var string
   */
  protected $cacheDir;

  /**
   * @var iSerializer
   */
  protected $serializer;

  /**
   * @var string
   */
  protected $fileMode = '0755';

  /**
   * @param string $cacheDir
   */
  public function __construct($cacheDir = null)
  {
    $this->serializer = new SerializerIgbinary();

    if (!$cacheDir) {
      $cacheDir = realpath(sys_get_temp_dir()) . '/simple_php_cache';
    }

    $this->cacheDir = (string)$cacheDir;

    if ($this->createCacheDirectory($cacheDir) === true) {
      $this->installed = true;
    }
  }

  /**
   * remove on cache-file
   *
   * @param string $key
   *
   * @return bool
   */
  public function remove($key)
  {
    $cacheFile = $this->getFileName($key);

    return $this->deleteFile($cacheFile);
  }

  /**
   * remove all cache-files
   *
   * @return bool
   */
  public function removeAll()
  {
    if (!$this->cacheDir) {
      return false;
    }

    $return = array();
    foreach (new \DirectoryIterator($this->cacheDir) as $fileInfo) {
      if(!$fileInfo->isDot()) {
        $return[] = unlink($fileInfo->getPathname());
      }
    }

    return !in_array(false, $return);
  }

  /**
   * @param string $key
   *
   * @return mixed
   */
  public function get($key)
  {
    $path = $this->getFileName($key);

    if (!file_exists($path)) {
      return null;
    }

    $data = $this->serializer->unserialize(file_get_contents($path));

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
   * @param $data
   *
   * @return bool
   */
  protected function validateDataFromCache($data)
  {
    if (!is_array($data)) {
      return false;
    }

    foreach (array('value', 'ttl') as $missing) {
      if (!array_key_exists($missing, $data)) {
        return false;
      }
    }

    return true;
  }

  /**
   * @param string $key
   *
   * @return bool
   */
  public function exists($key)
  {
    return null !== $this->get($key);
  }

  /**
   * @param string $key
   * @param mixed  $value
   *
   * @return bool
   */
  public function set($key, $value)
  {
    return $this->setExpired($key, $value);
  }

  /**
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return bool
   */
  public function setExpired($key, $value, $ttl = 0)
  {
    $item = $this->serializer->serialize(
        array(
            'value' => $value,
            'ttl'   => $ttl ? (int)$ttl + time() : 0,
        )
    );

    if (!file_put_contents($this->getFileName($key), $item)) {
      return false;
    }

    return true;
  }

  /**
   * recursively creates & chmod directories
   *
   * @param string $path
   *
   * @return bool
   */
  protected function createCacheDirectory($path)
  {
    if (
        !$path
        ||
        $path === '/'
        ||
        $path === '.'
        ||
        $path === '\\'
    ) {
      return false;
    }

    // if the directory already exists, just return true
    if (is_dir($path) && is_writable($path)) {
      return true;
    }

    // if more than one level, try parent first
    if (dirname($path) !== '.') {
      $return = $this->createCacheDirectory(dirname($path));
      // if creating parent fails, we can abort immediately
      if (!$return) {
        return false;
      }
    }

    $mode_dec = intval($this->fileMode, 8);
    $oldumask = umask(0);

    /** @noinspection PhpUsageOfSilenceOperatorInspection */
    if (!@mkdir($path, $mode_dec) && !is_dir($path)) {
      $return = false;
    } else {
      $return = true;
    }

    if (is_dir($path) && !is_writable($path)) {
      $return = chmod($path, $mode_dec);
    }

    umask($oldumask);

    return $return;
  }

  /**
   * @param $cacheFile
   *
   * @return bool
   */
  protected function deleteFile($cacheFile)
  {
    if (is_file($cacheFile)) {
      return unlink($cacheFile);
    }

    return false;
  }

  /**
   * @param string $key
   *
   * @return string
   */
  protected function getFileName($key)
  {
    return $this->cacheDir . DIRECTORY_SEPARATOR . self::CACHE_FILE_PREFIX . $key . self::CACHE_FILE_SUBFIX;
  }

  /**
   * @param $ttl
   *
   * @return bool
   */
  protected function ttlHasExpired($ttl)
  {
    if ($ttl === 0) {
      return false;
    }

    return (time() > $ttl);
  }

  /**
   * e.g. '0777', or '0755' ...
   *
   * @param $fileMode
   */
  public function setFileMode($fileMode)
  {
    $this->fileMode = $fileMode;
  }

  /**
   * check if cache is installed
   *
   * @return boolean
   */
  public function installed()
  {
    return $this->installed;
  }
}

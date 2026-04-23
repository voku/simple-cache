<?php

declare(strict_types=1);

namespace voku\cache;

/**
 * AdapterOpCache: PHP-OPcache
 *
 * OPcache improves PHP performance by storing precompiled script bytecode
 * in shared memory, thereby removing the need for PHP to load and
 * parse scripts on each request.
 */
class AdapterOpCache extends AdapterFileSimple
{
    /**
     * @var bool
     */
    private static $hasCompileFileFunction;

    /**
     * {@inheritdoc}
     */
    public function __construct($cacheDir = null)
    {
        parent::__construct($cacheDir);

        $this->serializer = new SerializerNo();

        if (self::$hasCompileFileFunction === null) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            self::$hasCompileFileFunction = (
                \function_exists('opcache_compile_file')
                &&
                \function_exists('opcache_get_status')
                &&
                @\opcache_get_status() !== false
            );
        }
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
        return $this->cacheDir . \DIRECTORY_SEPARATOR . self::CACHE_FILE_PREFIX . $key . '.php';
    }

    /**
     * {@inheritdoc}
     *
     * @noinspection PhpUndefinedClassInspection
     * @noinspection PhpUndefinedNamespaceInspection
     * @noinspection BadExceptionsProcessingInspection
     */
    public function setExpired(string $key, $value, int $ttl = 0): bool
    {
        $item = [
            'value' => $value,
            'ttl'   => $ttl ? $ttl + \time() : 0,
        ];
        if (\class_exists('\Symfony\Component\VarExporter\VarExporter')) {
            try {
                $content = \Symfony\Component\VarExporter\VarExporter::export($item);
            } catch (\Symfony\Component\VarExporter\Exception\ExceptionInterface $e) {
                $content = \var_export($item, true);
            }
        } else {
            $content = \var_export($item, true);
        }

        $content = '<?php return ' . $content . ';';

        $cacheFile = $this->getFileName($key);

        $result = $this->writeFile(
            $cacheFile,
            $content
        );

        if (
            $result === true
            &&
            self::$hasCompileFileFunction === true
        ) {
            // opcache will only compile and cache files older than the script execution start.
            // set a date before the script execution date, then opcache will compile and cache the generated file.
            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            \touch($cacheFile, \time() - 86400);

            /** @noinspection PhpComposerExtensionStubsInspection */
            \opcache_invalidate($cacheFile, true);
            /** @noinspection PhpComposerExtensionStubsInspection */
            \opcache_compile_file($cacheFile);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * <p>Overrides the base implementation because {@link AdapterOpCache} stores files with a
     * <code>.php</code> extension instead of the <code>.php.cache</code> extension used by
     * the other file-based adapters.</p>
     */
    public function getAllKeys(): array
    {
        if (!$this->cacheDir || !\is_dir($this->cacheDir)) {
            return [];
        }

        $prefix = static::CACHE_FILE_PREFIX;
        $suffix = '.php';
        $prefixLen = \strlen($prefix);
        $suffixLen = \strlen($suffix);
        $keys = [];

        foreach (new \DirectoryIterator($this->cacheDir) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) {
                continue;
            }

            $filename = $fileInfo->getFilename();

            // Match __simple_KEY.php but not __simple_KEY.php.cache
            if (
                \str_starts_with($filename, $prefix)
                &&
                \str_ends_with($filename, $suffix)
                &&
                !\str_ends_with($filename, '.php.cache')
            ) {
                $key = \substr($filename, $prefixLen, -$suffixLen);
                if ($key !== '') {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }
}

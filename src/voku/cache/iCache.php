<?php

declare(strict_types=1);

namespace voku\cache;

use DateTimeInterface;

/**
 * iCache: cache-global interface
 */
interface iCache
{
    /**
     * get item
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getItem(string $key);

    /**
     * set item
     *
     * @param string   $key
     * @param mixed    $value
     * @param int|null $ttl
     *
     * @return bool
     */
    public function setItem(string $key, $value, $ttl = 0): bool;

    /**
     * set item a special expire-date
     *
     * @param string             $key
     * @param mixed              $value
     * @param DateTimeInterface $date
     *
     * @return bool
     */
    public function setItemToDate(string $key, $value, DateTimeInterface $date): bool;

    /**
     * remove item
     *
     * @param string $key
     *
     * @return bool
     */
    public function removeItem(string $key): bool;

    /**
     * Remove all items whose keys match a given regular expression.
     *
     * @param string $pattern A valid PHP regular expression (e.g. '/^imagecache_/').
     *
     * @return bool
     *              <p>Returns true on success or when no items matched the pattern.
     *              Returns false if the adapter does not support key listing or a removal failed.</p>
     */
    public function removeItems(string $pattern): bool;

    /**
     * remove all items
     *
     * @return bool
     */
    public function removeAll(): bool;

    /**
     * check if item exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function existsItem(string $key): bool;
}

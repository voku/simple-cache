<?php

declare(strict_types=1);

namespace voku\cache;

/**
 * iSerializer: cache-serializer interface
 */
interface iSerializer
{
    /**
     * @param mixed $value
     */
    public function serialize($value);

    /**
     * @param string $value
     */
    public function unserialize($value);

    /**
     * @param array $options
     *
     * @return void
     */
    public function setUnserializeOptions(array $options);

    /**
     * @return string
     */
    public function getName(): string;
}

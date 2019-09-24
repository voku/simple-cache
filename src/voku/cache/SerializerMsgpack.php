<?php

declare(strict_types=1);

namespace voku\cache;

/**
 * SerializerMsgpack: serialize / unserialize
 */
class SerializerMsgpack implements iSerializer
{
    /**
     * @var bool
     */
    public static $_exists_msgpack;

    /**
     * @var null|array
     */
    private $unserialize_options;

    /**
     * SerializerIgbinary constructor.
     */
    public function __construct()
    {
        if (self::$_exists_msgpack === null) {
            self::$_exists_msgpack = (
                \function_exists('msgpack_pack')
                &&
                \function_exists('msgpack_unpack')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        if (self::$_exists_msgpack === true) {
            /** @noinspection PhpUndefinedFunctionInspection */
            /** @noinspection PhpComposerExtensionStubsInspection */
            return \msgpack_pack($value);
        }

        // fallback
        return \serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($value)
    {
        if (self::$_exists_msgpack === true) {
            /** @noinspection PhpUndefinedFunctionInspection */
            /** @noinspection PhpComposerExtensionStubsInspection */
            return \msgpack_unpack($value);
        }

        // fallback
        if ($this->unserialize_options !== null) {
            return \unserialize($value, $this->unserialize_options);
        }

        /** @noinspection UnserializeExploitsInspection */
        return \unserialize($value);
    }

    /**
     * @param array $options
     */
    public function setUnserializeOptions(array $options)
    {
        $this->unserialize_options = $options;
    }
}

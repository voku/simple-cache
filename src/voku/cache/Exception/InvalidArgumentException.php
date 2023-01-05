<?php

declare(strict_types=1);

namespace Voku\Cache\Exception;

class InvalidArgumentException extends \Exception implements \Psr\SimpleCache\InvalidArgumentException
{
}

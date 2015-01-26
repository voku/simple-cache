[![Build Status](https://travis-ci.org/voku/simple-cache.svg?branch=master)](https://travis-ci.org/voku/simple-cache)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f1ad7660-6b85-4e1e-a7a3-8489b96b64f8/mini.png)](https://insight.sensiolabs.com/projects/f1ad7660-6b85-4e1e-a7a3-8489b96b64f8)
[![Total Downloads](https://poser.pugx.org/voku/simple-cache/downloads.svg)](https://packagist.org/packages/voku/simple-cache)

Simple Cache Class
===================


This is a simple Cache Abstraction Layer for PHP >= 5.3 that provides a simple interaction with your cache-server.

_This project is under construction, any feedback would be appreciated_

Author: [Lars Moelleken](http://github.com/voku)


##Get "Simple Cache"
You can download it from here, or require it using [composer](https://packagist.org/packages/voku/simple-cache).
```json
{
    "require": {
		"voku/simple-cache": "dev-master"
	}
}
```

##Install via "composer require"
```shell
composer require voku/simple-cache
```


##Quick Start

```php
    require_once 'composer/autoload.php';

    $cache = new \voku\cache\Cache();
    
    // example
    // $cache = \voku\cache\Cache();
    // $cache->setItem('foo', 'bar');
    // $bar = $cache->getItem('foo');

```






[![Build Status](https://travis-ci.org/voku/simple-cache.svg?branch=master)](https://travis-ci.org/voku/simple-cache)
[![Coverage Status](https://coveralls.io/repos/voku/simple-cache/badge.svg)](https://coveralls.io/r/voku/simple-cache)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/voku/simple-cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/voku/simple-cache/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4926981d-ecb1-482b-a15c-447954b9bd66/mini.png)](https://insight.sensiolabs.com/projects/4926981d-ecb1-482b-a15c-447954b9bd66)
[![Reference Status](https://www.versioneye.com/php/voku:simple-cache/reference_badge.svg?style=flat)](https://www.versioneye.com/php/voku:simple-cache/references)
[![Dependency Status](https://www.versioneye.com/php/voku:simple-cache/dev-master/badge.svg)](https://www.versioneye.com/php/voku:simple-cache/dev-master)
[![Total Downloads](https://poser.pugx.org/voku/simple-cache/downloads.svg)](https://packagist.org/packages/voku/simple-cache)
[![License](https://poser.pugx.org/voku/simple-cache/license.svg)](https://packagist.org/packages/voku/simple-cache)
[![Join the chat at https://gitter.im/voku/simple-cache](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/voku/simple-cache?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


Simple Cache Class
===================

This is a simple Cache Abstraction Layer for PHP >= 5.3 that provides a simple interaction 
with your cache-server. You can define the Adapter / Serializer in the "constructor" or the class will auto-detect you server-cache in this order:
1. Memcached / Memcache
2. Redis
3. Xcache
4. APC / APCu
5. static array

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

##Usage 

```php
function test() {
  $cache = \voku\cache\Cache();
  
  if (
    $cache->getCacheIsReady() === true
    &&
    $cache->existsItem('foo')
  ) {
    return $cache->getItem('foo');
  } else {
    $bar = someSpecialFunctionsWithAReturnValue();
    $cache->setItem('foo', $bar);
    return $bar;
}
```



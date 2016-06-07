[![Build Status](https://travis-ci.org/voku/simple-cache.svg?branch=master)](https://travis-ci.org/voku/simple-cache)
[![Coverage Status](https://coveralls.io/repos/github/voku/simple-cache/badge.svg?branch=master)](https://coveralls.io/github/voku/simple-cache?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/voku/simple-cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/voku/simple-cache/?branch=master)
[![Codacy Badge](https://www.codacy.com/project/badge/5846d2a46599486486b3956c0ce11a18)](https://www.codacy.com/app/voku/simple-cache)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4926981d-ecb1-482b-a15c-447954b9bd66/mini.png)](https://insight.sensiolabs.com/projects/4926981d-ecb1-482b-a15c-447954b9bd66)
[![Reference Status](https://www.versioneye.com/php/voku:simple-cache/reference_badge.svg?style=flat)](https://www.versioneye.com/php/voku:simple-cache/references)
[![Dependency Status](https://www.versioneye.com/php/voku:simple-cache/dev-master/badge.svg)](https://www.versioneye.com/php/voku:simple-cache/dev-master)
[![Latest Stable Version](https://poser.pugx.org/voku/simple-cache/v/stable)](https://packagist.org/packages/voku/simple-cache) 
[![Total Downloads](https://poser.pugx.org/voku/simple-cache/downloads)](https://packagist.org/packages/voku/simple-cache) 
[![Latest Unstable Version](https://poser.pugx.org/voku/simple-cache/v/unstable)](https://packagist.org/packages/voku/simple-cache)
[![PHP 7 ready](http://php7ready.timesplinter.ch/voku/simple-cache/badge.svg)](https://travis-ci.org/voku/simple-cache)
[![License](https://poser.pugx.org/voku/simple-cache/license)](https://packagist.org/packages/voku/simple-cache)


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
    "voku/simple-cache": "2.*",
    "predis/predis": "1.*" // if you will use redis as cache, then add predis
  }
}
```

##Install via "composer require"
```shell
composer require voku/simple-cache
composer require predis/predis # if you will use redis as cache, then add predis
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
$cache = \voku\cache\Cache();
  
if ($cache->getCacheIsReady() === true && $cache->existsItem('foo')) {
  return $cache->getItem('foo');
} else {
  $bar = someSpecialFunctionsWithAReturnValue();
  $cache->setItem('foo', $bar);
  return $bar;
}
```

##No-Cache for the admin or a specific ip-address

If you use the parameter "$checkForUser" (true) from the constructor, then the cache isn't used for the admin-session.
You can also overwrite the check for the user, if you add a global function named "checkForDev()".



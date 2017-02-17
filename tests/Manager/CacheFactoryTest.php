<?php

namespace Magium\Configuration\Tests\Manager;

use Magium\Configuration\Manager\CacheFactory;
use PHPUnit\Framework\TestCase;
use Zend\Cache\Storage\Adapter\Filesystem;
use Zend\Cache\Storage\Adapter\Redis;

class CacheFactoryTest extends TestCase
{

    public function testBasicCacheConfiguration()
    {
        $simpleXml = simplexml_load_string(
            <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magium xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver></driver>
        <database></database>
    </persistenceConfiguration>
    <contextConfigurationFile file="asd" type="xml"/>
    <cache>
        <adapter>filesystem</adapter>
    </cache>
</magium>
XML
        );

        $cacheFactory = new CacheFactory();
        $cache = $cacheFactory->getCache($simpleXml->cache);
        self::assertInstanceOf(Filesystem::class, $cache);
    }
    public function testCacheConfigurationWithRecursiveOptions()
    {
        $redis = extension_loaded('redis');
        if (!$redis) {
            self::markTestSkipped('Redis extension is not loaded');
        }
        $simpleXml = simplexml_load_string(
            <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magium xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver></driver>
        <database></database>
    </persistenceConfiguration>
    <contextConfigurationFile file="asd" type="xml"/>
    <cache>
        <adapter>redis</adapter>
        <options>
                <server>localhost:6379</server>
        </options>
        
    </cache>
</magium>
XML
        );

        $cacheFactory = new CacheFactory();
        $methods = get_class_methods(new Redis());
        $cache = $cacheFactory->getCache($simpleXml->cache);
        self::assertInstanceOf(Redis::class, $cache);
    }

}

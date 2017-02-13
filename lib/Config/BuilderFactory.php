<?php

namespace Magium\Configuration\Config;

use Magium\Configuration\Config\Storage\RelationalDatabase;
use Magium\Configuration\Manager\CacheFactory;
use Zend\Db\Adapter\Adapter;

class BuilderFactory implements BuilderFactoryInterface
{
    protected $configuration;

    public function __construct(\SimpleXMLElement $configuration)
    {
        $this->configuration = $configuration;
    }

    protected function getCache(\SimpleXMLElement $element)
    {
        $cacheFactory = new CacheFactory();
        return $cacheFactory->getCache($element);
    }

    protected function getAdapter()
    {
        $config = json_encode($this->configuration->persistenceConfiguration);
        $config = json_decode($config, true);
        $adapter = new Adapter($config);
        $persistence = new RelationalDatabase($adapter);
        return $persistence;
    }

    protected function getSecureBaseDirectories()
    {
        $config = json_encode($this->configuration->baseDirectories);
        $config = json_decode($config, true);
        $baseDirs = [];
        if (is_array($config)) {
            // This code depends on chdir() having been called in MagiumConfigurationFactory
            foreach ($config as $dir) {
                $path = realpath($dir);
                if (!is_dir($path)) {
                    throw new InvalidConfigurationLocationException('A secure configuration path cannot be determined for the directory: ' . $dir);
                }
                $baseDirs[] = $path;
            }
        }
        return $baseDirs;
    }

    public function getBuilder()
    {
        // This method expects that chdir() has been called on the same level as the magium-configuration.xml file
        $cache = $this->getCache($this->configuration->cache);
        $persistence = $this->getAdapter();
        $secureBases = $this->getSecureBaseDirectories();

        /*
         * We only populate up to the secureBases because adding a DIC or service manager by configuration starts
         * making the configuration-based approach pay off less.  If you need a DIC or service manager for your
         * configuration builder (which you will if you use object/method callbacks for value filters) then you need
         * to wire the Builder object with your own code.
         */

        $builder = new Builder(
            $cache,
            $persistence,
            $secureBases
        );

        return $builder;
    }

}

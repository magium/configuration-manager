<?php

namespace Magium\Configuration\Manager;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\Config;
use Zend\Cache\Storage\StorageInterface;

class Manager
{

    protected $config = [];

    protected $cache;
    protected $localCache;
    protected $builder;
    protected $configurationLocation;

    public function __construct(
        StorageInterface $cache,
        Builder $builder,
        StorageInterface $localCache = null
    )
    {
        $this->cache = $cache;
        $this->builder = $builder;
        $this->localCache = $localCache;
     }

     public function getBuilder()
     {
         return $this->builder;
     }

    /**
     * @param string $context The (configurable) context for the needed configuration object
     * @return Config
     * @throws NoConfigurationException
     */

    public function getConfiguration($context = Config::CONTEXT_DEFAULT, $storeScopeLocally = false)
    {
        $key = 'current_cache_object_' . $context;
        if (isset($this->config[$key]) && $this->config[$key] instanceof Config) {
            return $this->config[$key];
        }

        // By default we will get the config location from the remote server for each request.  But we can override that.
        if ($this->localCache instanceof StorageInterface && $storeScopeLocally) {
            // If a local cache is defined we check there for the current scope key first
            $currentConfigItem = $this->localCache->getItem($key);
            // if we don't find the scope key we check the global cache
            if (!$currentConfigItem) {
                $currentConfigItem = $this->cache->getItem($key);
                // If the primary cache gives us a value we store it locally
                if ($currentConfigItem) {
                    $this->localCache->setItem($key, $currentConfigItem);
                }
            }
        } else {
            // Local cache is not configured, so we get the current config cache key from the global cache.
            $currentConfigItem = $this->cache->getItem($key);
        }

        $config = null;
        if ($this->localCache instanceof StorageInterface) {
            // First we check the local cache for the configuration object if it exists.
            $config = $this->localCache->getItem($currentConfigItem);
        }

        // Either way, if the config is null we check the global cache
        if ($config === null) {
            $config = $this->cache->getItem($currentConfigItem);
            if ($config !== null) {
                if ($this->localCache instanceof StorageInterface) {
                    $this->localCache->setItem($currentConfigItem, $config);
                }
             }
        }

        if ($config) {
            $config = new Config($config);
            $this->config[$key] = $config;
            return $this->config[$key];
        }
        $config = $this->builder->build($context);
        $this->config[$key] = $config;
        return $config;
    }

}

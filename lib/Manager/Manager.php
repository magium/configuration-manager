<?php

namespace Magium\Configuration\Manager;

use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\Repository\ConfigInterface;
use Zend\Cache\Storage\StorageInterface;

class Manager implements ManagerInterface
{

    protected $config = [];

    protected $cache;
    protected $localCache;
    protected $builder;
    protected $configurationLocation;
    protected $hashAlgo;
    protected $context;

    public function __construct(
        StorageInterface $cache = null,
        BuilderInterface $builder = null,
        StorageInterface $localCache = null,
        $context = ConfigurationRepository::CONTEXT_DEFAULT,
        $hashAlgo = 'sha1'
    )
    {
        $this->cache = $cache;
        $this->builder = $builder;
        $this->localCache = $localCache;
        $this->context = $context;
        $this->hashAlgo = $hashAlgo;
    }

    public function getBuilder()
    {
        return $this->builder;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function setLocalCache(StorageInterface $storage = null)
    {
        $this->localCache = $storage;
    }

    public function setRemoteCache(StorageInterface $storage)
    {
        $this->cache = $storage;
    }

    public function setBuilder(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }


    public function getContextCacheKey($context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        return strtoupper('current_cache_object_' . $context);
    }

    /**
     * @param string $context The (configurable) context for the needed configuration object
     * @return ConfigurationRepository
     * @throws NoConfigurationException
     */

    public function getConfiguration($context = null, $storeScopeLocally = false)
    {
        if ($context === null) {
            $context = $this->context;
        }
        $key = $this->getContextCacheKey($context);
        if (isset($this->config[$key]) && $this->config[$key] instanceof ConfigurationRepository) {
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
        if ($config === null && $currentConfigItem !== null) {
            $config = $this->cache->getItem($currentConfigItem);
            if ($config !== null) {
                if ($this->localCache instanceof StorageInterface) {
                    $this->localCache->setItem($currentConfigItem, $config);
                }
            }
        }

        if ($config) {
            $config = new ConfigurationRepository($config);
            $this->config[$key] = $config;
            return $this->config[$key];
        }
        $config = $this->builder->build($context);
        $this->storeConfigurationObject($config, $context);
        $this->config[$key] = $config;
        return $config;
    }

    public function storeConfigurationObject(ConfigurationRepository $config, $context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        $contextCacheKey = $this->getContextCacheKey($context);
        $previousConfigKey = $this->cache->getItem($contextCacheKey);
        $xml = $config->asXML();
        $configKey = hash_hmac($this->hashAlgo, $xml, '');
        $this->cache->addItem($configKey, $xml);
        $this->cache->setItem($contextCacheKey, $configKey);

        if ($previousConfigKey) {
            $this->cache->removeItem($previousConfigKey);
        }
    }

}

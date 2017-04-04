<?php

namespace Magium\Configuration\Tests\Factory;

use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Manager\ManagerInterface;
use Zend\Cache\Storage\StorageInterface;

class NotManagerManager implements ManagerInterface
{

    protected $configuration;
    protected $localCache;
    protected $remoteCache;
    protected $builder;

    /**
     * @return mixed
     */
    public function getLocalCache()
    {
        return $this->localCache;
    }

    /**
     * @return mixed
     */
    public function getRemoteCache()
    {
        return $this->remoteCache;
    }

    /**
     * @return mixed
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @param mixed $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration($context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        return $this->configuration;
    }

    public function setLocalCache(StorageInterface $storage = null)
    {
        $this->localCache = $storage;
    }

    public function setRemoteCache(StorageInterface $storage)
    {
        $this->remoteCache = $storage;
    }

    public function setBuilder(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }

}

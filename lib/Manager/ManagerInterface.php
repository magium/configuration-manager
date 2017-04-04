<?php

namespace Magium\Configuration\Manager;

use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\Repository\ConfigInterface;
use Zend\Cache\Storage\StorageInterface;

interface ManagerInterface
{

    /**
     * @param string $context
     * @return ConfigInterface
     */

    public function getConfiguration($context = ConfigurationRepository::CONTEXT_DEFAULT);

    public function setLocalCache(StorageInterface $storage = null);

    public function setRemoteCache(StorageInterface $storage);

    public function setBuilder(BuilderInterface $builder);

}

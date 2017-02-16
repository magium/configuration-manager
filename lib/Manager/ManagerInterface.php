<?php

namespace Magium\Configuration\Manager;

use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Config;
use Zend\Cache\Storage\StorageInterface;

interface ManagerInterface
{

    public function getConfiguration($context = Config::CONTEXT_DEFAULT);

    public function setLocalCache(StorageInterface $storage);

    public function setRemoteCache(StorageInterface $storage);

    public function setBuilder(BuilderInterface $builder);

}

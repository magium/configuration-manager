<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\MagiumConfigurationFactory;
use Magium\Configuration\MagiumConfigurationFactoryInterface;

trait ConfigurationFactoryTrait
{

    protected $factory;

    public function setConfigurationFactory(MagiumConfigurationFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    protected function getConfigurationFactory()
    {
        if (!$this->factory instanceof MagiumConfigurationFactoryInterface) {
            $this->factory = new MagiumConfigurationFactory();
        }
        return $this->factory;
    }
}

<?php

namespace Magium\Configuration\Config;

use Magium\Configuration\Config\Repository\ConfigInterface;

interface ConfigurationRepositoryAware
{

    public function setConfigurationRepository(ConfigInterface $config);

}

<?php

namespace Magium\Configuration\Config;

use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;

interface BuilderInterface
{

    /**
     * @param string $context
     * @param ConfigInterface|null $config
     * @return ConfigurationRepository
     */

    public function build($context = ConfigurationRepository::CONTEXT_DEFAULT, ConfigInterface $config = null);

    /**
     * @return MergedStructure
     */

    public function getMergedStructure();

}

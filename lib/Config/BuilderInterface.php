<?php

namespace Magium\Configuration\Config;

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

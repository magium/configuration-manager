<?php

namespace Magium\Configuration\Config;

interface BuilderInterface
{

    /**
     * @param string $context
     * @param ConfigInterface|null $config
     * @return Config
     */

    public function build($context = Config::CONTEXT_DEFAULT, ConfigInterface $config = null);

    /**
     * @return \SimpleXMLElement
     */

    public function getMergedStructure();

}

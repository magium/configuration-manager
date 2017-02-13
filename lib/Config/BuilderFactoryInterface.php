<?php

namespace Magium\Configuration\Config;

interface BuilderFactoryInterface
{

    /**
     * BuilderFactoryInterface constructor.  If you need something that does things differently, such as adding your
     * own service manager or dependency injection container, you should
     * override the MagiumConfigurationFactory class and make your own getBuilder() method.
     * @param \SimpleXMLElement $config
     */

    public function __construct(\SimpleXMLElement $config);

    public function getBuilder();

}

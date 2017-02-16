<?php

namespace Magium\Configuration\Config;

use Zend\Db\Adapter\Adapter;

interface BuilderFactoryInterface
{

    /**
     * BuilderFactoryInterface constructor.  If you need something that does things differently, such as adding your
     * own service manager or dependency injection container, you should
     * override the MagiumConfigurationFactory class and make your own getBuilder() method.
     * @param \SimpleXMLElement $config
     */

    public function __construct(\SimpleXMLElement $config);

    /**
     * @return Builder
     */

    public function getBuilder();

    /**
     * @return Adapter
     */

    public function getAdapter();

    /**
     * @return Storage\StorageInterface
     */

    public function getPersistence();

}

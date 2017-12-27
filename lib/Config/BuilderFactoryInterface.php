<?php

namespace Magium\Configuration\Config;

use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Zend\Db\Adapter\Adapter;

interface BuilderFactoryInterface
{

    /**
     * BuilderFactoryInterface constructor.  If you need something that does things differently, such as adding your
     * own service manager or dependency injection container, you should
     * override the MagiumConfigurationFactory class and make your own getBuilder() method.
     * @param \SplFileInfo $baseDirectory
     * @param \SimpleXMLElement $config
     * @param AbstractContextConfigurationFile $contextConfigurationFile
     */

    public function __construct(\SplFileInfo $baseDirectory, \SimpleXMLElement $config, AbstractContextConfigurationFile $contextConfigurationFile);

    /**
     * @return Builder
     */

    public function getBuilder();

    /**
     * @return Adapter
     */

    public function getRelationalAdapter();

    /**
     * @return Storage\StorageInterface
     */

    public function getPersistence();

}

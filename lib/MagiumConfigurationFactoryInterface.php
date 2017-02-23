<?php

namespace Magium\Configuration;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\BuilderFactoryInterface;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\Manager\ManagerInterface;

interface MagiumConfigurationFactoryInterface
{

    /**
     * @return AbstractContextConfigurationFile
     */

    public function getContextFile();

    /**
     * @return BuilderInterface
     */

    public function getBuilder();

    /**
     * @return ManagerInterface
     */

    public function getManager();

}

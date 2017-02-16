<?php

namespace Magium\Configuration\Manager;

use Magium\Configuration\Config\Config;

interface ManagerInterface
{

    public function getConfiguration($context = Config::CONTEXT_DEFAULT);

}

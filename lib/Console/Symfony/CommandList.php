<?php

namespace Magium\Configuration\Console\Symfony;

use Magium\Configuration\Console\Command\ConfigurationBuild;
use Magium\Configuration\Console\Command\ConfigurationGet;
use Magium\Configuration\Console\Command\ConfigurationList;
use Magium\Configuration\Console\Command\ConfigurationListKeys;
use Magium\Configuration\Console\Command\ConfigurationSet;
use Magium\Configuration\Console\Command\ContextList;
use Magium\Configuration\Console\Command\CreateTable;
use Magium\Configuration\Console\Command\DefaultCommand;
use Symfony\Component\Console\Application;

class CommandList
{

    public function addCommands(Application $application)
    {
        $default = new DefaultCommand(null, true);
        $application->add($default);
        $application->add(new ConfigurationBuild(null, true));
        $application->add(new ConfigurationGet(null, true));
        $application->add(new ConfigurationSet(null, true));
        $application->add(new ConfigurationListKeys(null, true));
        $application->add(new ContextList(null, true));
        $application->add(new CreateTable(null, true));
        $application->setDefaultCommand($default->getName());
    }

}

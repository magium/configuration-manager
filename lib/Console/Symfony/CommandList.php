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
        $application->add(new DefaultCommand());
        $application->add(new ConfigurationBuild());
        $application->add(new ConfigurationGet());
        $application->add(new ConfigurationSet());
        $application->add(new ConfigurationListKeys());
        $application->add(new ContextList());
        $application->add(new CreateTable());
        $application->setDefaultCommand(DefaultCommand::COMMAND);
    }

}

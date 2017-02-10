<?php

namespace Magium\Configuration\Console\Symfony;

use Magium\Configuration\Console\Command\ConfigurationList;
use Magium\Configuration\Console\Command\DefaultCommand;
use Symfony\Component\Console\Application;

class CommandList
{

    public function addCommands(Application $application)
    {
        $application->add(new DefaultCommand());
        $application->add(new ConfigurationList());
        $application->setDefaultCommand(DefaultCommand::COMMAND);
    }

}

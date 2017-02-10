<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class ConfigurationList extends Command
{

    const COMMAND = 'magium:configuration:list';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('List configuration settings')
            ->setHelp("This command lists all of the configuration setting values for the specified context, or 'default' if the context is not provided")
        ;

        $this->addArgument('path', InputArgument::REQUIRED, 'Configuration Path');
        $this->addArgument('context', InputArgument::OPTIONAL, 'Configuration Context', Config::CONTEXT_DEFAULT);
    }

}

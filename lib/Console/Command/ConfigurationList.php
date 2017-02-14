<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Config;
use Magium\Configuration\MagiumConfigurationFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = new MagiumConfigurationFactory();
        $factory->getBuilder();
    }

}

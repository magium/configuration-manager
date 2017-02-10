<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationGet extends Command
{

    const COMMAND = 'magium:configuration:get';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('Get a configuration value')
            ->setHelp("This command retrieves a value for a specific configuration path")
        ;

        $this->addArgument('path', InputArgument::REQUIRED, 'Configuration Path');
        $this->addArgument('context', InputArgument::OPTIONAL, 'Configuration Context', Config::CONTEXT_DEFAULT);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }


}

<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Config;
use Magium\Configuration\MagiumConfigurationFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationBuild extends Command
{

    const COMMAND = 'magium:configuration:build';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('Build configuration')
            ->setHelp(
                'This command will build the configuration object based off of configuration files and '
                . 'persistent storage data.  By default, it will rebuild all contexts, but you can specify an '
                . 'individual context if you so like.')
        ;

        $this->addArgument('context', InputArgument::OPTIONAL, 'Configuration Context (ignore to build all contexts)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = new MagiumConfigurationFactory();
        $builder = $factory->getBuilder();
        $contexts = [];
        $contextFile = $factory->getContextFile();
        if ($context = $input->getArgument('context')) {
            $contexts[] = $context;
        } else {
            $factory->
        }
    }

}

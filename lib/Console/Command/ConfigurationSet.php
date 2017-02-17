<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Config;
use Magium\Configuration\Config\ConfigInterface;
use Magium\Configuration\MagiumConfigurationFactory;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationSet extends Command
{

    const COMMAND = 'magium:configuration:set';

    protected $factory;

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('Set a configuration value')
            ->setHelp("This command sets a value for a specific configuration path")
        ;

        $this->addArgument('path', InputArgument::REQUIRED, 'Configuration Path');
        $this->addArgument('value', InputArgument::REQUIRED, 'Value');
        $this->addArgument('context', InputArgument::OPTIONAL, 'Configuration Context', Config::CONTEXT_DEFAULT);
    }

    public function setConfigurationFactory(MagiumConfigurationFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    protected function getConfigurationFactory()
    {
        if (!$this->factory instanceof MagiumConfigurationFactoryInterface) {
            $this->factory = new MagiumConfigurationFactory();
        }
        return $this->factory;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = $this->getConfigurationFactory();
        $builderFactory = $factory->getBuilderFactory();
        $path = $input->getArgument('path');
        $value = $input->getArgument('value');
        $context = $input->getArgument('context');
        $builderFactory->getPersistence()->setValue($path, $value, $context);

        $out = sprintf("Set %s to %s (context: %s)", $path, $value, $context);

        $output->writeln($out);
        $output->writeln("Don't forget to rebuild your configuration cache with " . ConfigurationBuild::COMMAND);
    }


}

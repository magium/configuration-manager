<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationSet extends AbstractCommand
{
    use ConfigurationFactoryTrait;

    const COMMAND = 'magium:configuration:set';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('Set a configuration value')
            ->setHelp("This command sets a value for a specific configuration path")
        ;

        $this->addArgument('path', InputArgument::REQUIRED, 'Configuration Path');
        $this->addArgument('value', InputArgument::OPTIONAL, 'Value');
        $this->addArgument('context', InputArgument::OPTIONAL, 'Configuration Context', ConfigurationRepository::CONTEXT_DEFAULT);
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = $this->getConfigurationFactory();
        $builderFactory = $factory->getBuilderFactory();
        $path = $input->getArgument('path');
        $value = $input->getArgument('value');
        $context = $input->getArgument('context');

        $structure = $factory->getBuilder()->getMergedStructure();

        $structure->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $paths = explode('/', $path);
        $xpath = '/';
        foreach ($paths as $pathName) {
            $xpath .= sprintf('/s:*[@identifier="%s"]', $pathName);
        }

        $results = $structure->xpath($xpath);
        if (!$results) {
            throw new UnconfiguredPathException(sprintf('Path (%s) is not configured.  Do you need to create a configuration file?', $path));
        }

        $builderFactory->getBuilder()->setValue($path, $value, $context);

        if (!$value) {
            $value = '<empty>';
        }

        $out = sprintf("Set %s to %s (context: %s)", $path, $value, $context);

        $output->writeln($out);
        $output->writeln("Don't forget to rebuild your configuration cache with " . ConfigurationBuild::COMMAND);
    }


}

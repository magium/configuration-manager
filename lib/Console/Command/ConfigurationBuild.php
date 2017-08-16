<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\InvalidContextException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationBuild extends AbstractCommand
{
    use ConfigurationFactoryTrait;

    const COMMAND = 'magium:configuration:build';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('Build configuration')
            ->setHelp(
                'This command will build the configuration object based off of configuration files and '
                . 'persistent storage data.  By default, it will rebuild all contexts, but you can specify an '
                . 'individual context if you so like.');

        $this->addArgument('context', InputArgument::OPTIONAL, 'Configuration Context (ignore to build all contexts)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = $this->getConfigurationFactory();
        $builder = $factory->getBuilder();
        $manager = $factory->getManager();
        $contexts = $factory->getContextFile()->getContexts();
        $context = $input->getArgument('context');
        if ($context) {
            if (in_array($context, $contexts)) {
                $contexts = [$context];
            } else {
                throw new InvalidContextException('Context does not exist: ' . $context);
            }
        }
        foreach ($contexts as $context) {
            $output->writeln('Building context: ' . $context);
            $config = $builder->build($context);
            $manager->storeConfigurationObject($config, $context);
        }
    }

}

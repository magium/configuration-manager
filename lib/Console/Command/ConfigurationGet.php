<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationGet extends AbstractCommand
{

    use ConfigurationFactoryTrait;

    const COMMAND = 'magium:configuration:get';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('Get a configuration value')
            ->setHelp("This command retrieves a value for a specific configuration path")
        ;
        $this->addOption('use-flag', 'f', InputOption::VALUE_NONE, 'Get value as flag');
        $this->addArgument('path', InputArgument::REQUIRED, 'Configuration Path');
        $this->addArgument('context', InputArgument::OPTIONAL, 'Configuration Context', ConfigurationRepository::CONTEXT_DEFAULT);
    }

    protected function getValueFlag(ConfigInterface $config, $path)
    {
        $value = $config->getValueFlag($path);
        if ($value) {
            $value = 'flag:true';
        } else {
            $value = 'flag:false';
        }
        return $value;
    }

    protected function getValue(ConfigInterface $config, $path)
    {
        $value = $config->getValue($path);
        if (is_null($value)) {
            $value = '<null>';
        } else if (!is_string($value) && !is_numeric($value)) {
            $type = gettype($value);
            $value = json_encode($value);
            $value = sprintf('%s:%s', $type, $value);
        } else if ($value == '') {
            $value = '<empty>';
        }
        return $value;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = $this->getConfigurationFactory();
        $manager = $factory->getManager();
        $path = $input->getArgument('path');
        $context = $input->getArgument('context');
        $config = $manager->getConfiguration($context);
        $useFlag = $input->getOption('use-flag');
        if ($useFlag) {
            $value = $this->getValueFlag($config, $path);
        } else {
            $value = $this->getValue($config, $path);
        }
        $out = sprintf("Value for %s (context: %s): %s", $path, $context, $value);

        $output->writeln($out);
    }


}

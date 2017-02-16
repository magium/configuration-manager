<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Config;
use Magium\Configuration\MagiumConfigurationFactory;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationGet extends Command
{

    const COMMAND = 'magium:configuration:get';

    protected $factory;

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('Get a configuration value')
            ->setHelp("This command retrieves a value for a specific configuration path")
        ;
        $this->addOption('use-flag', 'f', InputOption::VALUE_NONE, 'Get value as flag');
        $this->addArgument('path', InputArgument::REQUIRED, 'Configuration Path');
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
        $manager = $factory->getManager();
        $path = $input->getArgument('path');
        $context = $input->getArgument('context');
        $config = $manager->getConfiguration($context);
        $useFlag = $input->getOption('use-flag');
        if ($useFlag) {
            $value = $config->getValueFlag($path);
            if ($value) {
                $value = 'flag:true';
            } else {
                $value = 'flag:false';
            }
        } else {
            $value = $config->getValue($path);
            if (is_null($value)) {
                $value = '<null>';
            } else if (!is_string($value) && !is_numeric($value)) {
                ob_start();
                var_dump($value);
                $value = ob_get_clean();
                $value = trim($value);
            } else if ($value == '') {
                $value = '<empty>';
            }
        }
        $out = sprintf("Value for %s (context: %s): %s", $path, $context, $value);

        $output->writeln($out);
    }


}

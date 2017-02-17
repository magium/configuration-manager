<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Config;
use Magium\Configuration\MagiumConfigurationFactory;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationListKeys extends Command
{

    const COMMAND = 'magium:configuration:list-keys';

    const INITIAL_MESSAGE = 'Valid configuration keys';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('List configuration settings')
            ->setHelp("This command lists all of the configuration setting options")
        ;
    }

    public function getConfigurationFactory()
    {
        if (!$this->factory instanceof MagiumConfigurationFactoryInterface) {
            $this->factory = new MagiumConfigurationFactory();
        }
        return $this->factory;
    }

    public function setConfigurationFactory(MagiumConfigurationFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = $this->getConfigurationFactory();
        $builder = $factory->getBuilder();
        $merged = $builder->getMergedStructure();
        $merged->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $elements = $merged->xpath('//s:element');
        $output->writeln(self::INITIAL_MESSAGE);
        foreach ($elements as $element) {
            $elementId = (string)$element['id'];
            $parent = $element->xpath('..');
            $groupId = (string)$parent[0]['id'];
            $parent = $parent[0]->xpath('..');
            $sectionId = (string)$parent[0]['id'];
            $description = '';
            if (isset($element->description)) {
                $description = sprintf(' (%s)', (string)$element->description);
            }

            $out = sprintf('%s/%s/%s%s', $sectionId, $groupId, $elementId, $description);
            $output->writeln($out);

        }
    }

}

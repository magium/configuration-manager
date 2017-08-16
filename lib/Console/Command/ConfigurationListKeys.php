<?php

namespace Magium\Configuration\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationListKeys extends AbstractCommand
{
    use ConfigurationFactoryTrait;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = $this->getConfigurationFactory();
        $builder = $factory->getBuilder();
        $merged = $builder->getMergedStructure();
        $merged->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $elements = $merged->xpath('//s:element');
        $output->writeln(self::INITIAL_MESSAGE);
        foreach ($elements as $element) {
            $elementId = (string)$element['identifier'];
            $parent = $element->xpath('..');
            $groupId = (string)$parent[0]['identifier'];
            $parent = $parent[0]->xpath('..');
            $sectionId = (string)$parent[0]['identifier'];
            $default = '';
            if (isset($element->value)) {
                $default = sprintf(' (default: %s) ', (string)$element->value);
            }
            if (isset($element->description)) {
                $description = sprintf("%s\n        (%s)", $default, (string)$element->description);
            } else {
                $description = $default;
            }

            $out = sprintf("%s/%s/%s%s\n", $sectionId, $groupId, $elementId, $description);
            $output->writeln($out);

        }
    }

}

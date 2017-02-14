<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Config;
use Magium\Configuration\MagiumConfigurationFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ContextList extends Command
{
    const TAB = '    ';
    const COMMAND = 'magium:configuration:list-contexts';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('List contexts')
            ->setHelp("This command lists the context hierarchy")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Context List');
        $output->writeln(['Following is list of contexts shown by inheritance', '']);
        $factory = new MagiumConfigurationFactory();
        $contextFile = $factory->getContextFile();
        $context = $contextFile->toXml();
        $output->writeln($this->formatNode('default', 'Default'));
        foreach ($context->children() as $child) {
            $this->writeNode($child, $output);
        }
    }

    protected function formatNode($id, $name = null)
    {
        if ($name == null) {
            $name = $id;
        }
        return sprintf('%s (%s)', $id, $name);
    }

    protected function writeNode(\SimpleXMLElement $element, OutputInterface $output, $tab = self::TAB)
    {
        $id = (string)$element['id'];
        $title = (string)$element['title'];
        $output->writeln($tab . $this->formatNode($id, $title));
        foreach ($element->children() as $child) {
            $this->writeNode($child, $output, $tab . self::TAB);
        }
    }

}

<?php

namespace Magium\Configuration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class DefaultCommand extends Command
{
    const COMMAND = 'magium:configuration:init';

    protected function configure()
    {
        $this->setName(self::COMMAND)->setHelp('Initializes Magium Configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = __DIR__;
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $path = preg_replace('@/lib/.+@', '', $path);
        $paths = explode('/', $path);
        array_shift($paths); // Probably should not be in the root path...
        $configurationLocation = realpath(DIRECTORY_SEPARATOR);
        $possibleLocations = [];
        $foundVendor = false;
        foreach ($paths as $path) {
            $configurationLocation .= DIRECTORY_SEPARATOR . $path;
            $configurationLocation = realpath($configurationLocation);
            $file = $configurationLocation . DIRECTORY_SEPARATOR . 'magium-configuration.xml';

            if (file_exists($file)) {
                $command = $this->getApplication()->find('list');
                $command->run($input, $output);
                return;
            }
            if (basename($path) == 'vendor') {
                $foundVendor = true;
            }
            if (!$foundVendor) {
                $possibleLocations[] = $file;
            }
        }

        $question = new ChoiceQuestion(
            'Could not find a magium-configuration.xml file.  Where would you like me to put it?',
            $possibleLocations
        );
        $result = $this->getHelper('question')->ask($input, $output, $question);
        file_put_contents($result, <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<configuration xmlns="http://www.magiumlib.com/BaseConfiguration">
    <cache>
        <adapter>file</adapter>
        <options/>
    </cache>
</configuration>
XML
);
        $output->writeln('Wrote XML configuration file to: ' . $result);
    }


}

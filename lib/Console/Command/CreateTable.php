<?php

namespace Magium\Configuration\Console\Command;

use Magium\Configuration\Config\Config;
use Magium\Configuration\Config\InvalidContextException;
use Magium\Configuration\MagiumConfigurationFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTable extends Command
{

    use ConfigurationFactoryTrait;

    const COMMAND = 'magium:configuration:create-table';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setDescription('Create configuration table in the database')
            ->setHelp(
                'This command will create the table for holding configuration values in the configured database.')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Creating configuration table...');
        $factory = $this->getConfigurationFactory();
        $persistence = $factory->getBuilderFactory()->getPersistence();
        try {
            $persistence->create();
            $output->writeln('Table created');
        } catch (\Exception $e) {
            $output->writeln('Unable to create table: ' . $e->getMessage());
        }
    }

}

<?php

namespace Magium\Configuration\Tests\Command;

use Magium\Configuration\Config\BuilderFactory;
use Magium\Configuration\Config\BuilderFactoryInterface;
use Magium\Configuration\Config\Config;
use Magium\Configuration\Config\Storage\StorageInterface;
use Magium\Configuration\Console\Command\ConfigurationSet;
use Magium\Configuration\MagiumConfigurationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationSetTest extends TestCase
{

    public function testSetWorks()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(3))->method('getArgument')->willReturnCallback(function($param) {
            $values = [
                'path'  => 'path value',
                'value' => 'string value',
                'context'   => 'context name'
            ];
            TestCase::assertContains($param, array_keys($values));
            return $values[$param];
        });
        $persistence = $this->getPersistence();
        $persistence->expects(self::once())->method('setValue')->with(
            self::equalTo('path value'),
            self::equalTo('string value'),
            self::equalTo('context name')
        );

        $this->execute($persistence, $input);
    }

    protected function execute(StorageInterface $persistence, InputInterface $input)
    {
        $builderFactory = $this->getBuilderFactory();
        $builderFactory->expects(self::once())->method('getPersistence')->willReturn($persistence);
        /* @var $builderFactory BuilderFactoryInterface */
        /* @var $input InputInterface */
        $factory = $this->getFactory($builderFactory);
        $command = new ConfigurationSet();
        $command->setConfigurationFactory($factory);
        $command->run(
            $input,
            $this->createMock(OutputInterface::class)
        );
    }

    protected function getPersistence()
    {
        $persistence = $this->getMockBuilder(StorageInterface::class)->disableOriginalConstructor()->setMethods(
            get_class_methods(StorageInterface::class)
        )->getMock();
        return $persistence;
    }

    protected function getBuilderFactory()
    {
        $factory = $this->getMockBuilder(BuilderFactory::class)->disableOriginalConstructor()->setMethods(
            ['getPersistence']
        )->getMock();
        return $factory;
    }

    protected function getFactory(BuilderFactoryInterface $builderFactory)
    {
        $factory = $this->getMockBuilder(MagiumConfigurationFactory::class)->disableOriginalConstructor()->setMethods(
            ['getBuilderFactory']
        )->getMock();
        $factory->expects(self::once())->method('getBuilderFactory')->willReturn($builderFactory);
        return $factory;
    }


}

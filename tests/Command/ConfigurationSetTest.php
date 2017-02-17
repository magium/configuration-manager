<?php

namespace Magium\Configuration\Tests\Command;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\BuilderFactory;
use Magium\Configuration\Config\BuilderFactoryInterface;
use Magium\Configuration\Config\Config;
use Magium\Configuration\Config\Storage\StorageInterface;
use Magium\Configuration\Console\Command\ConfigurationSet;
use Magium\Configuration\Console\Command\UnconfiguredPathException;
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
                'path'  => 'section/group/element',
                'value' => 'string value',
                'context'   => 'context name'
            ];
            TestCase::assertContains($param, array_keys($values));
            return $values[$param];
        });
        $persistence = $this->getPersistence();
        $persistence->expects(self::once())->method('setValue')->with(
            self::equalTo('section/group/element'),
            self::equalTo('string value'),
            self::equalTo('context name')
        );

        $this->execute($input, $persistence);
    }
    public function testInvalidPathThrowsException()
    {
        $this->expectException(UnconfiguredPathException::class);
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(3))->method('getArgument')->willReturnCallback(function($param) {
            $values = [
                'path'  => 'section/group/element-wrong',
                'value' => 'string value',
                'context'   => 'context name'
            ];
            TestCase::assertContains($param, array_keys($values));
            return $values[$param];
        });
        $this->execute($input);
    }

    protected function execute(InputInterface $input, StorageInterface $persistence = null)
    {
        $builderFactory = $this->getBuilderFactory();
        if ($persistence instanceof StorageInterface) {
            $builderFactory->expects(self::once())->method('getPersistence')->willReturn($persistence);
        } else {
            $builderFactory->expects(self::never())->method('getPersistence');
        }
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

    protected function getBuilder()
    {
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods([
            'getMergedStructure'
        ])->getMock();
        $builder->expects(self::once())->method('getMergedStructure')->willReturn(new \SimpleXMLElement(
            <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<configuration xmlns="http://www.magiumlib.com/Configuration">
    <section id="section">
        <group id="group">
            <element id="element"></element></group></section></configuration>
XML
        ));
        return $builder;
    }

    protected function getFactory(BuilderFactoryInterface $builderFactory)
    {
        $factory = $this->getMockBuilder(MagiumConfigurationFactory::class)->disableOriginalConstructor()->setMethods(
            ['getBuilderFactory', 'getBuilder']
        )->getMock();
        $factory->expects(self::once())->method('getBuilderFactory')->willReturn($builderFactory);
        $factory->expects(self::once())->method('getBuilder')->willReturn($this->getBuilder());
        return $factory;
    }


}

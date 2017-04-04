<?php

namespace Magium\Configuration\Tests\Command;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\BuilderFactory;
use Magium\Configuration\Config\BuilderFactoryInterface;
use Magium\Configuration\Config\BuilderInterface;
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
                'value' => 'to blave',
                'context'   => 'context name'
            ];
            TestCase::assertContains($param, array_keys($values));
            return $values[$param];
        });
        $persistence = $this->getPersistence();
        $persistence->expects(self::once())->method('setValue')->with(
            self::equalTo('section/group/element'),
            self::equalTo('to blave'),
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
        $builder = $this->getBuilder();
        if ($persistence instanceof StorageInterface) {
            $builder->expects(self::once())->method('getStorage')->willReturn($persistence);
        } else {
            $builder->expects(self::never())->method('getStorage');
        }
        /* @var $builder BuilderInterface */
        /* @var $input InputInterface */
        $factory = $this->getFactory($this->getBuilderFactory($builder), $builder);
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

    protected function getBuilderFactory(BuilderInterface $builder = null)
    {
        $factory = $this->getMockBuilder(BuilderFactory::class)->disableOriginalConstructor()->setMethods(
            ['getPersistence', 'getBuilder']
        )->getMock();
        if ($builder instanceof BuilderInterface) {
            $factory->expects(self::any())->method('getBuilder')->willReturn($builder);
        }
        return $factory;
    }

    protected function getBuilder(StorageInterface $persistence = null)
    {
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods([
            'getMergedStructure', 'getStorage'
        ])->getMock();
        if ($persistence instanceof StorageInterface) {
            $builder->expects(self::once())->method('getStorage')->willReturn($persistence);
        }
        $builder->expects(self::atLeast(1))->method('getMergedStructure')->willReturn(new \SimpleXMLElement(
            <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
    <section identifier="section">
        <group identifier="group">
            <element identifier="element">
                <permittedValues>
                    <value>to blave</value>
                </permittedValues>
            </element>
        </group>
    </section>
</magiumConfiguration>
XML
        ));
        return $builder;
    }

    protected function getFactory(BuilderFactoryInterface $builderFactory = null, BuilderInterface $builder = null)
    {
        $factory = $this->getMockBuilder(MagiumConfigurationFactory::class)->disableOriginalConstructor()->setMethods(
            ['getBuilderFactory', 'getBuilder']
        )->getMock();
        if ($builderFactory instanceof BuilderFactoryInterface) {
            $factory->expects(self::any())->method('getBuilderFactory')->willReturn($builderFactory);
        }

        if ($builder instanceof BuilderInterface) {
            $factory->expects(self::any())->method('getBuilder')->willReturn($builder);
        } else {
            $factory->expects(self::any())->method('getBuilder')->willReturn($this->getBuilder());
        }
        return $factory;
    }


}

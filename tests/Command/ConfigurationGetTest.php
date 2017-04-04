<?php

namespace Magium\Configuration\Tests\Command;

use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Console\Command\ConfigurationGet;
use Magium\Configuration\MagiumConfigurationFactory;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Magium\Configuration\Manager\Manager;
use Magium\Configuration\Manager\ManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationGetTest extends CommandTestCase
{

    public function testConfiguration()
    {
        $command = new ConfigurationGet();
        $name = $command->getName();
        self::assertEquals(ConfigurationGet::COMMAND, $name);

        $synopsis  = $command->getSynopsis();
        self::assertContains('context', $synopsis);
        self::assertContains('use-flag', $synopsis);
        self::assertContains('path', $synopsis);
    }

    public function testThatConfigurationIsPrinted()
    {
        $factory = $this->getFactory($this->getManager($this->getConfig('path', 'string value')));
        $output = $this->getMockBuilder(OutputInterface::class)->setMethods(get_class_methods(OutputInterface::class))->getMock();
        $output->expects(self::once())->method('writeln')->with(self::stringContains('string value'));
        $this->executeTest($factory, $output);
    }

    public function testThatNullConfigurationUsesNull()
    {
        $factory = $this->getFactory($this->getManager($this->getConfig('path', null)));
        $output = $this->getMockBuilder(OutputInterface::class)->setMethods(get_class_methods(OutputInterface::class))->getMock();
        $output->expects(self::once())->method('writeln')->with(self::stringContains('<null>'));
        $this->executeTest($factory, $output);
    }

    public function testThatEmptyConfigurationIsEmpty()
    {
        $factory = $this->getFactory($this->getManager($this->getConfig('path', '')));
        $output = $this->getMockBuilder(OutputInterface::class)->setMethods(get_class_methods(OutputInterface::class))->getMock();
        $output->expects(self::once())->method('writeln')->with(self::stringContains('<empty>'));
        $this->executeTest($factory, $output);
    }

    public function testThatTypedConfigurationShowsType()
    {
        $factory = $this->getFactory($this->getManager($this->getConfig('path', true)));
        $output = $this->getMockBuilder(OutputInterface::class)->setMethods(get_class_methods(OutputInterface::class))->getMock();
        $output->expects(self::once())->method('writeln')->with(self::stringContains('boolean:true'));
        $this->executeTest($factory, $output);
    }

    public function testTrueFlagIsIndicated()
    {
        $factory = $this->getFactory($this->getManager($this->getConfig('path', true, true)));
        $output = $this->getMockBuilder(OutputInterface::class)->setMethods(get_class_methods(OutputInterface::class))->getMock();
        $output->expects(self::once())->method('writeln')->with(self::stringContains('flag:true'));
        $this->executeTest($factory, $output, true);
    }

    public function testFalseFlagIsIndicated()
    {
        $factory = $this->getFactory($this->getManager($this->getConfig('path', false, true)));
        $output = $this->getMockBuilder(OutputInterface::class)->setMethods(get_class_methods(OutputInterface::class))->getMock();
        $output->expects(self::once())->method('writeln')->with(self::stringContains('flag:false'));
        $this->executeTest($factory, $output, true);
    }

    protected function getConfig($path, $returnValue, $useFlag = false)
    {
        $config = $this->getMockBuilder(ConfigInterface::class)->setMethods(['getValue' ,'getValueFlag', 'hasValue', 'xpath'])->getMock();
        if ($useFlag) {
            $config->expects(self::once())->method('getValueFlag')->with(self::equalTo($path))->willReturn($returnValue);
            $config->expects(self::never())->method('getValue');
        } else {
            $config->expects(self::once())->method('getValue')->with(self::equalTo($path))->willReturn($returnValue);
            $config->expects(self::never())->method('getValueFlag');
        }
        return $config;
    }


    protected function executeTest(
        MagiumConfigurationFactoryInterface $factory,
        OutputInterface $output,
        $withFlag = false)
    {
        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $input->expects(self::exactly(2))->method('getArgument')->willReturnOnConsecutiveCalls('path', 'context');
        if ($withFlag) {
            $input->expects(self::once())->method('getOption')->with(self::equalTo('use-flag'))->willReturn(true);
        } else {
            $input->expects(self::once())->method('getOption')->with(self::equalTo('use-flag'))->willReturn(false);
        }

        $command = new ConfigurationGet();
        $command->setConfigurationFactory($factory);
        $command->run(
            $input,
            $output
        );
    }

    protected function getManager(ConfigInterface $config)
    {
        $mock = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->setMethods(
            ['getConfiguration']
        )->getMock();
        $mock->expects(self::once())->method('getConfiguration')->willReturn($config);
        return $mock;
    }

    protected function getFactory(ManagerInterface $manager)
    {
        $factory = $this->getMockBuilder(MagiumConfigurationFactory::class)->disableOriginalConstructor()->setMethods(
            ['getManager']
        )->getMock();
        $factory->expects(self::exactly(1))->method('getManager')->willReturn($manager);
        return $factory;
    }

}

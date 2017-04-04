<?php

namespace Magium\Configuration\Tests\Command;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\InvalidContextException;
use Magium\Configuration\Console\Command\ConfigurationBuild;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\MagiumConfigurationFactory;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use Magium\Configuration\Manager\Manager;
use Magium\Configuration\Manager\ManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationBuilderTest extends CommandTestCase
{

    public function testConfiguration()
    {
        $command = new ConfigurationBuild();
        $name = $command->getName();
        self::assertEquals(ConfigurationBuild::COMMAND, $name);

        $synopsis  = $command->getSynopsis();
        self::assertContains('context', $synopsis);
    }

    public function testThatConfigurationIsBuilt()
    {

        $builder = $this->getBuilder(2);

        $manager = $this->getManager();
        $manager->expects(self::exactly(2))->method('storeConfigurationObject')->willReturnCallback(
            function ($config, $context) {
                static $key = 0;
                $options = [
                    'context1', 'context2'
                ];
                TestCase::assertInstanceOf(ConfigurationRepository::class, $config);
                self::assertEquals($options[$key++], $context);
            }
        );

        $contextFile = $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock();
        $contextFile->expects(self::once())->method('getContexts')->willReturn([
            'context1',
            'context2'
        ]);

        $factory = $this->getFactory($builder, $manager, $contextFile);
        /* @var $factory \Magium\Configuration\MagiumConfigurationFactoryInterface */

        $this->executeTest($factory);
    }

    public function testThatOnlyOneContextIsBuilt()
    {

        $builder = $this->getBuilder(1);

        $manager = $this->getManager();
        $manager->expects(self::once())->method('storeConfigurationObject')->with(
            self::isInstanceOf(ConfigurationRepository::class),
            self::equalTo('context')
        );

        $contextFile = $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock();
        $contextFile->expects(self::once())->method('getContexts')->willReturn([
            'context',
        ]);

        $factory = $this->getFactory($builder, $manager, $contextFile);
        /* @var $factory \Magium\Configuration\MagiumConfigurationFactoryInterface */

        $this->executeTest($factory);
    }

    public function testThatInvalidContextThrowsException()
    {
        $this->expectException(InvalidContextException::class);
        $builder = $this->getBuilder(0);
        $manager = $this->getManager();

        $contextFile = $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock();
        $contextFile->expects(self::once())->method('getContexts')->willReturn([
            'context1',
        ]);

        $factory = $this->getFactory($builder, $manager, $contextFile);
        /* @var $factory \Magium\Configuration\MagiumConfigurationFactoryInterface */

        $input = $this->createMock(InputInterface::class);

        // The return value "context" does not match "context1" in the $contextFile mock, triggering the exception
        $input->expects(self::once())->method('getArgument')->with(self::equalTo('context'))->willReturn('context');

        $this->executeTest($factory, $input);
    }

    public function testOnlyProvidedContextIsRun()
    {
        $builder = $this->getBuilder(1);
        $manager = $this->getManager();

        $contextFile = $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock();
        $contextFile->expects(self::once())->method('getContexts')->willReturn([
            'context1',
        ]);

        $factory = $this->getFactory($builder, $manager, $contextFile);
        /* @var $factory \Magium\Configuration\MagiumConfigurationFactoryInterface */

        $input = $this->createMock(InputInterface::class);

        // The return value "context" does not match "context1" in the $contextFile mock, triggering the exception
        $input->expects(self::once())->method('getArgument')->with(self::equalTo('context'))->willReturn('context1');

        $this->executeTest($factory, $input);
    }

    protected function executeTest(
        MagiumConfigurationFactoryInterface $factory,
        InputInterface $input = null)
    {
        if (!$input instanceof InputInterface) {
            $input = $this->getMockBuilder(InputInterface::class)->getMock();
        }
        $command = new ConfigurationBuild();
        $command->setConfigurationFactory($factory);
        $command->run(
            $input,
            $this->getMockBuilder(OutputInterface::class)->getMock()
        );
    }

    protected function getBuilder($count)
    {
        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods(
            ['build']
        )->getMock();
        $builder->expects(self::exactly($count))->method('build')->willReturn(new ConfigurationRepository('<config />'));
        return $builder;
    }

    protected function getManager()
    {
        $mock = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->setMethods(
            ['storeConfigurationObject']
        )->getMock();
        return $mock;
    }

    protected function getFactory(BuilderInterface $builder, ManagerInterface $manager, AbstractContextConfigurationFile $contextFile)
    {
        $factory = $this->getMockBuilder(MagiumConfigurationFactory::class)->disableOriginalConstructor()->setMethods(
            ['getBuilder', 'getManager', 'getContextFile']
        )->getMock();
        $factory->expects(self::exactly(1))->method('getBuilder')->willReturn($builder);
        $factory->expects(self::exactly(1))->method('getManager')->willReturn($manager);
        $factory->expects(self::exactly(1))->method('getContextFile')->willReturn($contextFile);
        return $factory;
    }

}

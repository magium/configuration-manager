<?php

namespace Magium\Configuration\Tests\Command;

use Magium\Configuration\Console\Command\DefaultCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultCommandTest extends CommandTestCase
{

    const CONFIGURATION_FILE_NAME = 'magium-configuration.xml';
    const TESTS_CONFIGURATION_FILE_NAME = 'tests/magium-configuration.xml';
    const CONTEXT_FILE_NAME = 'contexts.xml';

    protected function setUp()
    {
        parent::setUp();
        $this->removeRealPath(self::CONFIGURATION_FILE_NAME);
        $this->removeRealPath(self::TESTS_CONFIGURATION_FILE_NAME);
        $this->removeRealPath(self::CONTEXT_FILE_NAME);
    }

    protected function removeRealPath($file)
    {
        $path = $this->getRealPath($file);
        if ($path && file_exists($path)) {
            unlink($path);
        }
    }

    protected function getRealPath($file)
    {
        return realpath(__DIR__ . '/../../' . $file);
    }

    public function testCorrectClassIsReturnedForCommand()
    {
        $application = $this->getApplication();
        $application->add(new DefaultCommand());
        $command = $application->get(DefaultCommand::COMMAND);
        self::assertInstanceOf(DefaultCommand::class, $command);
    }

    public function testRootDirectoryIsNotIncludedInPossibleLocations()
    {
        $paths = $this->getPaths();
        $path = realpath('/');
        self::assertNotFalse($paths);
        self::assertNotContains($path, $paths);
    }

    public function testCwdDirectoryIsNotIncludedInPossibleLocations()
    {
        $cwd = getcwd();
        $paths = $this->getPaths();
        self::assertNotFalse($paths);
        self::assertNotContains($cwd, $paths);
    }

    protected function getPaths()
    {
        $method = new \ReflectionMethod(DefaultCommand::class, 'getPossibleLocations');
        $method->setAccessible(true);
        $paths = $method->invoke(new DefaultCommand());
        return $paths;
    }


    public function testValidMagiumConfigurationFileIsCreated()
    {
        $command = $this->getMockBuilder(DefaultCommand::class)
                        ->setMethods(['askConfigurationQuestion', 'askContextFileQuestion'])
                        ->getMock();
        /* @var $command DefaultCommand */
        $paths = $this->getPaths();
        $path = array_pop($paths);
        $command->expects(self::once())->method('askConfigurationQuestion')->willReturn($path);
        $command->expects(self::once())->method('askContextFileQuestion')->willReturn(false);
        $command->run(
            $this->getMockBuilder(InputInterface::class)->getMock(),
            $this->getMockBuilder(OutputInterface::class)->getMock()
        );
        self::assertFileExists($this->getRealPath(self::CONFIGURATION_FILE_NAME));
        self::assertFalse($this->getRealPath(self::CONTEXT_FILE_NAME));
        $doc = new \DOMDocument();
        $doc->load($path);
        $schemaPath = realpath(__DIR__ . '/../../assets/magium-configuration.xsd');
        self::assertNotFalse($schemaPath);
        $result = $doc->schemaValidate($schemaPath);
        self::assertTrue($result);
    }

    public function testContextFileCreatedWhenAskedToTo()
    {
        $command = $this->getMockBuilder(DefaultCommand::class)
            ->setMethods(['askConfigurationQuestion', 'askContextFileQuestion'])
            ->getMock();
        /* @var $command DefaultCommand */
        $paths = $this->getPaths();
        $path = array_pop($paths);
        $command->expects(self::once())->method('askConfigurationQuestion')->willReturn($path);
        $command->expects(self::once())->method('askContextFileQuestion')->willReturn(true);
        $command->run(
            $this->getMockBuilder(InputInterface::class)->getMock(),
            $this->getMockBuilder(OutputInterface::class)->getMock()
        );
        self::assertFileExists($this->getRealPath(self::CONFIGURATION_FILE_NAME));
        self::assertFileExists($this->getRealPath(self::CONTEXT_FILE_NAME));
        $doc = new \DOMDocument();
        $doc->load($this->getRealPath(self::CONTEXT_FILE_NAME));
        $schemaPath = realpath(__DIR__ . '/../../assets/context.xsd');
        self::assertNotFalse($schemaPath);
        $result = $doc->schemaValidate($schemaPath);
        self::assertTrue($result);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->removeRealPath(self::CONFIGURATION_FILE_NAME);
        $this->removeRealPath(self::CONTEXT_FILE_NAME);
    }

}

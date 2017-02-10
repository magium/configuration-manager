<?php

namespace Magium\Configuration\Tests\Factory;

use Magium\Configuration\InvalidConfigurationFileException;
use Magium\Configuration\MagiumConfigurationFactory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{

    protected $configFile = null;

    protected function setFile($contents = '<config />')
    {
        $this->configFile = __DIR__ . '/../../magium-configuration.xml';
        file_put_contents($this->configFile, $contents);
        parent::setUp();
    }

    protected function tearDown()
    {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
        parent::tearDown();
    }

    public function testExistingConfigFile()
    {
        $this->setFile();
        $path = realpath($this->configFile);
        new MagiumConfigurationFactory($path);
    }

    public function testFindExistingConfigFile()
    {
        $this->setFile();
        new MagiumConfigurationFactory();
    }
    public function testInvalidConfigFileThrowsException()
    {
        $this->expectException(InvalidConfigurationFileException::class);
        new MagiumConfigurationFactory();
    }

}

<?php

namespace Magium\Configuration\Tests\Factory;

use Magium\Configuration\InvalidConfigurationFileException;
use Magium\Configuration\MagiumConfigurationFactory;
use Magium\Configuration\Manager\Manager;
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

    public function testValidateDocumentSucceeds()
    {
        $this->setValidFile();
        $path = realpath($this->configFile);
        $factory = new MagiumConfigurationFactory($path);
        $result = $factory->validateConfigurationFile();
        self::assertTrue($result);
    }

    public function testValidateDocumentFailsWithImproperConfigFile()
    {
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<configuration>
    <contextConfigurationFile file="contexts.xml" />
    <cached />
</configuration>

XML
);
        $path = realpath($this->configFile);
        $factory = new MagiumConfigurationFactory($path);
        $result = $factory->validateConfigurationFile();
        self::assertFalse($result);
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

    public function testGetManager()
    {
        $this->setValidFile();
        $factory = new MagiumConfigurationFactory();
        $manager = $factory->getManager();
        self::assertInstanceOf(Manager::class, $manager);
    }

    protected function setValidFile()
    {
        $tmp = sys_get_temp_dir();
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<configuration xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
    <contextConfigurationFile file="contexts.xml" />
    <cache>
        <adapter>filesystem</adapter>
        <options>
            <cache_dir>$tmp</cache_dir>
        </options>
    </cache>
</configuration>

XML
        );
    }

}

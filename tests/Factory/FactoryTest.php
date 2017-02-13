<?php

namespace Magium\Configuration\Tests\Factory;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\InvalidConfigurationLocationException;
use Magium\Configuration\InvalidConfigurationFileException;
use Magium\Configuration\MagiumConfigurationFactory;
use Magium\Configuration\Manager\Manager;
use PHPUnit\Framework\TestCase;
use Zend\Cache\Storage\StorageInterface;

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

    public function testLocalCacheIsCreatedIfItIsConfigured()
    {
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<configuration xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
    <cache>
        <adapter>filesystem</adapter>
    </cache>
    <localCache>
        <adapter>filesystem</adapter>
    </localCache>
</configuration>

XML
        );
        $path = realpath($this->configFile);
        $factory = $this->getMockBuilder(
            MagiumConfigurationFactory::class
        )->setMethods(['getCache', 'getBuilder'])
            ->setConstructorArgs([$path])
            ->getMock();
        $factory->expects(self::exactly(2))->method('getCache')->willReturn(
            $this->getMockBuilder(StorageInterface::class)->disableOriginalConstructor()->getMock()
        );
        $factory->expects(self::exactly(1))->method('getBuilder')->willReturn(
            $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->getMock()
        );
        /* @var $factory MagiumConfigurationFactory */
        $manager = $factory->getManager();
        self::assertInstanceOf(Manager::class, $manager);
    }

    public function testLocalCacheIsNotCreatedIfItIsNotConfigured()
    {
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<configuration xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
    <cache>
        <adapter>filesystem</adapter>
    </cache>
</configuration>

XML
        );
        $path = realpath($this->configFile);
        $factory = $this->getMockBuilder(
            MagiumConfigurationFactory::class
        )->setMethods(['getCache', 'getBuilder'])
            ->setConstructorArgs([$path])
            ->getMock();
        $factory->expects(self::exactly(1))->method('getCache')->willReturn(
            $this->getMockBuilder(StorageInterface::class)->disableOriginalConstructor()->getMock()
        );
        $factory->expects(self::exactly(1))->method('getBuilder')->willReturn(
            $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->getMock()
        );
        /* @var $factory MagiumConfigurationFactory */
        $manager = $factory->getManager();
        self::assertInstanceOf(Manager::class, $manager);
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

    public function testInvalidBaseDirectoryThrowsException()
    {
        $base = $tmp = sys_get_temp_dir();
        $base .= DIRECTORY_SEPARATOR . 'remove-me'; // Won't exist
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<configuration xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
      <persistenceConfiguration>
        <driver>pdo_sqlite</driver>
        <database>:memory:</database>
    </persistenceConfiguration>
    <baseDirectories><directory>$base</directory></baseDirectories>
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
        $factory = new MagiumConfigurationFactory();
        $this->expectException(InvalidConfigurationLocationException::class);
        $factory->getManager();
    }

    protected function setValidFile()
    {
        $tmp = sys_get_temp_dir();
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<configuration xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
      <persistenceConfiguration>
        <driver>pdo_sqlite</driver>
        <database>:memory:</database>
    </persistenceConfiguration>
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

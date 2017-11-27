<?php

namespace Magium\Configuration\Tests\Factory;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\Context;
use Magium\Configuration\Config\InvalidConfigurationLocationException;
use Magium\Configuration\Config\MissingConfigurationException;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\File\Context\XmlFile;
use Magium\Configuration\InvalidConfigurationException;
use Magium\Configuration\InvalidConfigurationFileException;
use Magium\Configuration\MagiumConfigurationFactory;
use Magium\Configuration\Manager\Manager;
use PHPUnit\Framework\TestCase;
use Zend\Cache\Storage\StorageInterface;

class FactoryTest extends TestCase
{

    const CONFIG = 'magium-configuration.xml';

    protected $configFile = [];

    protected function setFile($contents = '<config />', $filename = self::CONFIG)
    {
        $this->configFile[$filename] = __DIR__ . '/../../' . $filename;
        file_put_contents($this->configFile[$filename], $contents);
        return $this->configFile[$filename];
    }

    protected function tearDown()
    {
        foreach ($this->configFile as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    public function testContextIsReferencedCorrectly()
    {
        $context = $this->createMock(Context::class);
        $context->expects(self::once())->method('getContext')->willReturn('blah');
        chdir(__DIR__);
        new MagiumConfigurationFactory(realpath('magium-configuration.xml'), $context);
    }

    public function testExistingConfigFile()
    {
        $this->setFile();
        $path = realpath($this->configFile[self::CONFIG]);
        $factory = new MagiumConfigurationFactory($path);

        self::assertEquals($path, $factory->getMagiumConfigurationFilePath());
    }


    public function testEnvironmentVariableOverrideReturnsValue()
    {
        $this->setFile();
        $factory = new MagiumConfigurationFactory();
        putenv('MCM_CACHE_OPTIONS_SERVER=boogers');
        $value = $factory->getEnvironmentVariableOverride('cache/options/server');
        self::assertEquals('boogers', $value);
    }

    public function testEnvironmentVariableOverrideXmlValue()
    {
        putenv('MCM_CACHE_OPTIONS_SERVER=boogers');
        $this->setFile(file_get_contents(__DIR__ . '/magium-configuration.xml'));
        $factory = new MagiumConfigurationFactory();
        $xml = $factory->getXml();
        $value = (string)$xml->cache->options->server;
        self::assertEquals('boogers', $value);
    }

    public function testValidateDocumentSucceeds()
    {
        $this->setValidFile();
        $path = realpath($this->configFile[self::CONFIG]);
        $factory = new MagiumConfigurationFactory($path);
        $result = $factory->validateConfigurationFile();
        self::assertTrue($result);
    }

    public function testLocalCacheIsCreatedIfItIsConfigured()
    {
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
    <cache>
        <adapter>filesystem</adapter>
    </cache>
    <localCache>
        <adapter>filesystem</adapter>
    </localCache>
</magiumBase>

XML
        );
        $path = realpath($this->configFile[self::CONFIG]);
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
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
    <cache>
        <adapter>filesystem</adapter>
    </cache>
</magiumBase>

XML
        );
        $path = realpath($this->configFile[self::CONFIG]);
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

    protected function setContextFileConfiguration()
    {
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
    <contextConfigurationFile file="contexts.xml" type="xml"/>
</magiumBase>

XML
        );
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumDefaultContext xmlns="http://www.magiumlib.com/ConfigurationContext">
    <context id="production" label="Production">
        <context id="store1" label="Store 1" />
    </context>
    <context id="development" label="Development" />
</magiumDefaultContext>

XML
            ,'contexts.xml');
    }

    public function testFactoryParsesContextFileProperly()
    {
        $this->setContextFileConfiguration();
        $path = realpath($this->configFile[self::CONFIG]);
        $factory = new MagiumConfigurationFactory($path);
        $context = $factory->getContextFile();
        self::assertInstanceOf(AbstractContextConfigurationFile::class, $context);
    }

    public function testMissingContextFileThrowsException()
    {
        $this->expectException(MissingConfigurationException::class);
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
    <contextConfigurationFile file="contexts.xml" type="xml"/>
</magiumBase>

XML
        );
        $path = realpath($this->configFile[self::CONFIG]);
        $factory = new MagiumConfigurationFactory($path);
        $factory->getContextFile();
    }

    public function testInvalidContextFileThrowsException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magium >
    <contextConfigurationFile file="contexts.xml" type="AbstractContextConfiguration"/>
</magium>

XML
        );
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumDefaultContext xmlns="http://www.magiumlib.com/ConfigurationContext">
    
</magium>
XML
            ,'contexts.xml');
        $path = realpath($this->configFile[self::CONFIG]);
        $factory = new MagiumConfigurationFactory($path);
        $factory->getContextFile();
    }

    public function testValidateDocumentFailsWithImproperConfigFile()
    {
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumConfiguration>
    <contextConfigurationFile file="contexts.xml" />
    <cached />
</magiumConfiguration>

XML
);
        $path = realpath($this->configFile[self::CONFIG]);
        $factory = new MagiumConfigurationFactory($path);
        $result = $factory->validateConfigurationFile();
        self::assertFalse($result);
    }

    public function testFindExistingConfigFile()
    {
        $filename = realpath($this->setFile());
        $factory = new MagiumConfigurationFactory();

        self::assertEquals($filename, $factory->getMagiumConfigurationFilePath());
    }
    public function testInvalidConfigFileThrowsException()
    {
        $this->expectException(InvalidConfigurationFileException::class);
        new MagiumConfigurationFactory();
    }

    public function testInvalidBuilderFactoryTypeThrowsException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
    <builderFactory class="ArrayObject" />
</magiumBase>

XML
        );
        $factory = new MagiumConfigurationFactory();
        $factory->getBuilderFactory();
    }

    public function testGetManager()
    {
        $this->setValidFile();
        $this->setContextFile();
        $factory = new MagiumConfigurationFactory();
        $manager = $factory->getManager();
        self::assertInstanceOf(Manager::class, $manager);
    }

    public function testGetManagerStatic()
    {
        $this->setValidFile();
        $this->setContextFile();
        $manager = MagiumConfigurationFactory::managerFactory();
        self::assertInstanceOf(Manager::class, $manager);
    }

    public function testGetBuilderStatic()
    {
        $this->setValidFile();
        $this->setContextFile();
        $builder = MagiumConfigurationFactory::builderFactory();
        self::assertInstanceOf(Builder::class, $builder);
    }

    protected function setContextFile()
    {
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8" ?>
<magiumDefaultContext xmlns="http://www.magiumlib.com/ConfigurationContext"/>
XML
,
    'contexts.xml'
    );
    }

    public function testGetNotManagerAndMakeSureSettersAreCalled()
    {
        $this->setContextFile();
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
          <persistenceConfiguration><driver>pdo_sqlite</driver><database>:memory:</database></persistenceConfiguration>
      <manager class="Magium\Configuration\Tests\Factory\NotManagerManager" />
      <cache><adapter>filesystem</adapter></cache>
      <localCache><adapter>filesystem</adapter></localCache>
</magiumBase>

XML
        );
        $factory = $this->getMockBuilder(MagiumConfigurationFactory::class)->setMethods(['getContextFile'])->getMock();
        $factory->expects(self::once())->method('getContextFile')->willReturn(new XmlFile($this->configFile['contexts.xml']));
        $manager = $factory->getManager();
        self::assertInstanceOf(NotManagerManager::class, $manager);
        /* @var $manager NotManagerManager */
        self::assertInstanceOf(Builder::class, $manager->getBuilder());
        self::assertInstanceOf(StorageInterface::class, $manager->getLocalCache());
        self::assertInstanceOf(StorageInterface::class, $manager->getRemoteCache());
    }

    public function testInvalidManagerThrowsException()
    {
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
          <persistenceConfiguration><driver>pdo_sqlite</driver><database>:memory:</database></persistenceConfiguration>
      <manager class="ArrayObject" />
      <cache><adapter>filesystem</adapter></cache>
      <localCache><adapter>filesystem</adapter></localCache>
</magiumBase>

XML
        );
        $this->expectException(InvalidConfigurationException::class);
        $factory = new MagiumConfigurationFactory();
        $factory->getManager();
    }

    public function testInvalidBaseDirectoryThrowsException()
    {
        $base = $tmp = sys_get_temp_dir();
        $base .= DIRECTORY_SEPARATOR . 'remove-me'; // Won't exist
        $this->setContextFile();
        $this->setFile(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
      <persistenceConfiguration>
        <driver>pdo_sqlite</driver>
        <database>:memory:</database>
    </persistenceConfiguration>
    <contextConfigurationFile file="contexts.xml" type="xml" />
    <configurationDirectories><directory>$base</directory></configurationDirectories>
    <cache>
        <adapter>filesystem</adapter>
        <options>
            <cache_dir>$tmp</cache_dir>
        </options>
    </cache>
</magiumBase>

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
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/BaseConfiguration">
      <persistenceConfiguration>
        <driver>pdo_sqlite</driver>
        <database>:memory:</database>
    </persistenceConfiguration>
    <contextConfigurationFile file="contexts.xml" type="xml"/>
    <cache>
        <adapter>filesystem</adapter>
        <options>
            <cache_dir>$tmp</cache_dir>
        </options>
    </cache>
</magiumBase>

XML
        );
    }

}

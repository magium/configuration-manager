<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\BuilderFactory;
use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\Config\Storage\Mongo;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

class BuilderFactoryTest extends TestCase
{

    public function testBuilderMustPointToRealDirectory()
    {
        $this->expectException(InvalidConfigurationException::class);
        new BuilderFactory(
            new \SplFileInfo('boogers'),
            new \SimpleXMLElement('<test />'),
            $this->createMock(AbstractContextConfigurationFile::class)
        );
    }

    public function testSingleConfigurationDirectory()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationDirectories><directory>xml</directory></configurationDirectories>
</magiumBase>
XML
);

        $dirs = $this->runConfigurationDirectory($config);
        self::assertCount(1, $dirs);
        self::assertContains(realpath(__DIR__ . '/xml'), $dirs);
    }

    public function testSingleConfigurationDirectoryParent()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationDirectories><directory>..</directory></configurationDirectories>
</magiumBase>
XML
);

        $dirs = $this->runConfigurationDirectory($config);
        self::assertCount(1, $dirs);
        self::assertContains(realpath(__DIR__ . '/..'), $dirs);
    }

    public function testMutipleConfigurationDirectory()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationDirectories><directory>not-supported</directory><directory>xml</directory></configurationDirectories>
</magiumBase>
XML
);

        $dirs = $this->runConfigurationDirectory($config);
        self::assertCount(2, $dirs);
        self::assertContains(realpath(__DIR__ . '/xml'), $dirs);
        self::assertContains(realpath(__DIR__ . '/not-supported'), $dirs);
    }

    public function testGetOneConfigurationFile()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationFiles><file>config-merge-1.xml</file></configurationFiles>
</magiumBase>
XML
        );
        $dirs = [realpath(__DIR__ . '/xml')];
        $files = $this->runConfigurationFiles($config, $dirs);
        self::assertCount(1, $files);
        self::assertContains(realpath(__DIR__ . '/xml/config-merge-1.xml'), $files);
    }

    public function testMongoDsn()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>mongo</driver>
        <database>database</database>
        <hostname>hostname</hostname>
        <table>table</table>
        <username>username</username>
        <password>password</password>
        <port>27017</port>
        </persistenceConfiguration>
</magiumBase>
XML
        );
        $builderFactory = new BuilderFactory(
            new \SplFileInfo(__DIR__),
            $config,
            $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock()
        );
        $dsn = $builderFactory->getMongoDsnString();
        self::assertEquals('mongodb://username:password@hostname:27017', $dsn);
    }

    public function testExceptionThrownWithoutADriver()
    {
        $this->expectException(\Exception::class);
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration />
</magiumBase>
XML
        );
        $builderFactory = new BuilderFactory(
            new \SplFileInfo(__DIR__),
            $config,
            $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock()
        );
        $builderFactory->getPersistence();
    }

    public function testExceptionThrownWithoutADatabase()
    {
        $this->expectException(\Exception::class);
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
    <driver>mongo</driver>
    </persistenceConfiguration>
</magiumBase>
XML
        );
        $builderFactory = new BuilderFactory(
            new \SplFileInfo(__DIR__),
            $config,
            $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock()
        );
        $builderFactory->getPersistence();
    }

    public function testMongoDsnWithRequiredOnly()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>mongo</driver>
        <database>database</database>
        <hostname>hostname</hostname>
    </persistenceConfiguration>
</magiumBase>
XML
        );
        $builderFactory = new BuilderFactory(
            new \SplFileInfo(__DIR__),
            $config,
            $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock()
        );
        $dsn = $builderFactory->getMongoDsnString();
        self::assertEquals('mongodb://hostname', $dsn);
    }

    public function testMongoAdapter()
    {
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('Mongo extension not installed');
        }

        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>mongo</driver>
        <database>database</database>
        <hostname>localhost</hostname>
    </persistenceConfiguration>
</magiumBase>
XML
        );
        $builderFactory = new BuilderFactory(
            new \SplFileInfo(__DIR__),
            $config,
            $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock()
        );
        $mongo = $builderFactory->getMongoAdapter();
        $collection = $mongo->getCollection();
        $name = $collection->getCollectionName();
        self::assertEquals(Mongo::TABLE, $name);
    }

    public function testMongoAdapterWithBespokeCollection()
    {
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('Mongo extension not installed');
        }

        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>mongo</driver>
        <database>database</database>
        <table>test_collection</table>
        <hostname>localhost</hostname>
    </persistenceConfiguration>
</magiumBase>
XML
        );
        $builderFactory = new BuilderFactory(
            new \SplFileInfo(__DIR__),
            $config,
            $this->getMockBuilder(AbstractContextConfigurationFile::class)->disableOriginalConstructor()->getMock()
        );
        $mongo = $builderFactory->getMongoAdapter();
        $collection = $mongo->getCollection();
        $name = $collection->getCollectionName();
        self::assertEquals('test_collection', $name);
    }

    public function testGetMultipleConfigurationFiles()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationFiles><file>config-merge-1.xml</file><file>config-merge-2.xml</file></configurationFiles>
</magiumBase>
XML
        );
        $dirs = [realpath(__DIR__ . '/xml')];
        $files = $this->runConfigurationFiles($config, $dirs);
        self::assertCount(2, $files);
        self::assertContains(realpath(__DIR__ . '/xml/config-merge-1.xml'), $files);
    }

    public function testGetMultipleConfigurationFilesFromMultipleDirectories()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumBase xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationFiles><file>config-merge-1.xml</file><file>test.unsupported</file></configurationFiles>
</magiumBase>
XML
        );
        $dirs = [
            realpath(__DIR__ . '/xml'),
            realpath(__DIR__ . '/not-supported'),
        ];
        $files = $this->runConfigurationFiles($config, $dirs);
        self::assertCount(2, $files);
        self::assertContains(realpath(__DIR__ . '/xml/config-merge-1.xml'), $files);
        self::assertContains(realpath(__DIR__ . '/not-supported/test.unsupported'), $files);
    }

    protected function runConfigurationFiles(\SimpleXMLElement $config, array $secureDirs)
    {
        $factory = $this->getFactory($config);
        return $factory->getConfigurationFiles($secureDirs);
    }

    protected function getFactory(\SimpleXMLElement $config)
    {
        $factory = new BuilderFactory(
            new \SplFileInfo(__DIR__),
            $config,
            $this->createMock(AbstractContextConfigurationFile::class)
        );
        return $factory;
    }

    protected function runConfigurationDirectory(\SimpleXMLElement $config)
    {
        $factory = $this->getFactory($config);
        $dirs = $factory->getSecureBaseDirectories();
        return $dirs;
    }

}

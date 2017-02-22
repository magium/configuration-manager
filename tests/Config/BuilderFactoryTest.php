<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\BuilderFactory;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use PHPUnit\Framework\TestCase;

class BuilderFactoryTest extends TestCase
{

    public function testSingleConfigurationDirectory()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magium xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationDirectories><directory>xml</directory></configurationDirectories>
</magium>
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
<magium xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationDirectories><directory>..</directory></configurationDirectories>
</magium>
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
<magium xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationDirectories><directory>not-supported</directory><directory>xml</directory></configurationDirectories>
</magium>
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
<magium xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationFiles><file>config-merge-1.xml</file></configurationFiles>
</magium>
XML
        );
        $dirs = [realpath(__DIR__ . '/xml')];
        $files = $this->runConfigurationFiles($config, $dirs);
        self::assertCount(1, $files);
        self::assertContains(realpath(__DIR__ . '/xml/config-merge-1.xml'), $files);
    }

    public function testGetMultipleConfigurationFiles()
    {
        $config = new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magium xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationFiles><file>config-merge-1.xml</file><file>config-merge-2.xml</file></configurationFiles>
</magium>
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
<magium xmlns="http://www.magiumlib.com/BaseConfiguration">
    <persistenceConfiguration>
        <driver>
</driver><database></database></persistenceConfiguration><contextConfigurationFile file="" type="xml"/>
<configurationFiles><file>config-merge-1.xml</file><file>test.unsupported</file></configurationFiles>
</magium>
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
            new \SplFileInfo(__DIR__ . '../../'),
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

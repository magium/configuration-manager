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
        self::assertContains(realpath('xml'), $dirs);
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
        self::assertContains(realpath('..'), $dirs);
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
        self::assertContains(realpath('xml'), $dirs);
        self::assertContains(realpath('not-supported'), $dirs);
    }

    protected function runConfigurationDirectory(\SimpleXMLElement $config)
    {
        $factory = new BuilderFactory(
            new \SplFileInfo(__DIR__ . '../../'),
            $config,
            $this->createMock(AbstractContextConfigurationFile::class)
        );
        $dirs = $factory->getSecureBaseDirectories();
        return $dirs;
    }

}

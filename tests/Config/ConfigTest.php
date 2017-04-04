<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\Repository\ConfigurationRepository;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{

    public function testGetValue()
    {
        $config = $this->getConfig();
        self::assertEquals('value', $config->getValue('section/group/element'));
        self::assertNull($config->getValue('section/group/element2'));
    }

    public function testGetFlag()
    {
        $config = $this->getConfig();
        self::assertTrue($config->getValueFlag('section/group/true1'));
        self::assertTrue($config->getValueFlag('section/group/true2'));
        self::assertTrue($config->getValueFlag('section/group/true3'));
        self::assertFalse($config->getValueFlag('section/group/element'));
    }

    public function testHasValue()
    {
        $config = $this->getConfig();
        self::assertTrue($config->hasValue('section/group/true1'));
        self::assertTrue($config->hasValue('section/group/empty'));
        self::assertFalse($config->hasValue('section/group/novalue'));
    }

    protected function getConfig()
    {
        return new ConfigurationRepository(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<configuration>
    <section>
        <group>
            <true1>1</true1>
            <true2>on</true2>
            <true3>yes</true3>
            <element>value</element>
            <empty />
        </group>
    </section>
</configuration>

XML
        );
    }

}

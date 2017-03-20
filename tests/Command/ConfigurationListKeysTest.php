<?php

namespace Magium\Configuration\Tests\Command;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\MergedStructure;
use Magium\Configuration\Console\Command\ConfigurationList;
use Magium\Configuration\Console\Command\ConfigurationListKeys;
use Magium\Configuration\File\Configuration\AbstractConfigurationFile;
use Magium\Configuration\MagiumConfigurationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationListKeysTest extends TestCase
{

    protected $files = [];

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testSlug()
    {
        $command = new ConfigurationListKeys();
        self::assertEquals(ConfigurationListKeys::COMMAND, $command->getName());
    }

    public function testStructure()
    {
        $xml1 = $this->createMock(AbstractConfigurationFile::class);
        $xml1->expects(self::once())->method('toXml')->willReturn(
            new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
    <section identifier="section">
        <group identifier="group">
            <element identifier="element1">
                <description>This is element 1</description>
            </element>
        </group>
    </section>
</magiumConfiguration>
XML
)
        );
        $xml2 = $this->createMock(AbstractConfigurationFile::class);
        $xml2->expects(self::once())->method('toXml')->willReturn(
            new MergedStructure(<<<XML
<magiumConfiguration xmlns="http://www.magiumlib.com/Configuration">
    <section identifier="section">
        <group identifier="group">
            <element identifier="element2">
                <description>The description</description>
                <value>default value</value>
            </element>
        </group>
    </section>
</magiumConfiguration>
XML
            )
        );

        $builder = $this->getMockBuilder(Builder::class)->disableOriginalConstructor()->setMethods(
            ['getRegisteredConfigurationFiles']
        )->getMock();
        $builder->expects(self::once())->method('getRegisteredConfigurationFiles')->willReturn([
            $xml1, $xml2
        ]);

        $factory = $this->createMock(MagiumConfigurationFactory::class);
        $factory->expects(self::once())->method('getBuilder')->willReturn($builder);

        $command = new ConfigurationListKeys();
        $command->setConfigurationFactory($factory);
        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::at(0))->method('writeln')->with(ConfigurationListKeys::INITIAL_MESSAGE);
        $output->expects(self::at(1))->method('writeln')->with(self::stringContains('This is element 1'));
        $output->expects(self::at(2))->method('writeln')->with(self::stringContains('section/group/element2'));
        $command->run(
            $this->createMock(InputInterface::class),
            $output
        );
    }

}

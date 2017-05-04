<?php

namespace Magium\Configuration\Tests\Command;

use Magium\Configuration\Console\Command\ContextList;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\MagiumConfigurationFactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ContextListTest extends TestCase
{

    public function testConfiguration()
    {
        $command = new ContextList();
        $name = $command->getName();
        self::assertEquals(ContextList::COMMAND, $name);
    }

    public function testIndentationIsCorrect()
    {
        self::assertEquals('    ', ContextList::TAB);
    }

    public function testGetFormat()
    {

        $config = $this->createMock(AbstractContextConfigurationFile::class);
        $config->expects(self::once())->method('toXml')->willReturn(
            new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<magiumDefaultContext xmlns="http://www.magiumlib.com/ConfigurationContext">
    <context id="test" label="Test">
        <context label="SubTest" id="subtest" />
    </context>
    <context id="base"/>
</magiumDefaultContext>
XML
            )
        );
        $factory = $this->createMock(MagiumConfigurationFactoryInterface::class);
        $factory->expects(self::once())->method('getContextFile')->willReturn($config);
        $contextList = new ContextList();
        $contextList->setConfigurationFactory($factory);

        $output = $this->createMock(OutputInterface::class);
        $expected = [
            'default (Default)',
            ContextList::TAB . 'test (Test)',
            ContextList::TAB . ContextList::TAB . 'subtest (SubTest)',
            ContextList::TAB . 'base',
        ];
        $output->expects(self::any())->method('writeln')->willReturnCallback(function($param) use (&$expected) {
            $key = array_search($param, $expected);
            if ($key !== false) {
                unset($expected[$key]);
            }
        });

        $contextList->run($this->createMock(InputInterface::class), $output);

        self::assertCount(0, $expected);
    }


}

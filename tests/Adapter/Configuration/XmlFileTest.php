<?php

namespace Magium\Configuration\Tests\Adapter\Configuration;

use Magium\Configuration\File\InvalidFileException;
use Magium\Configuration\File\InvalidFileStructureException;
use Magium\Configuration\File\Configuration\XmlFile;
use PHPUnit\Framework\TestCase;

class XmlFileTest extends TestCase
{

    public function testInvalidFileThrowsException()
    {
        $this->expectException(InvalidFileException::class);
        new XmlFile('non-existent-file');
    }

    public function testInValidInclusion()
    {
        $this->expectException(InvalidFileStructureException::class);
        $file = new XmlFile(__DIR__ . '/xml/config-bad.xml');
        $file->toXml();
    }

    public function testValidInclusion()
    {
        $file = new XmlFile(__DIR__ . '/xml/config.xml');
        $config = $file->toXml();
        self::assertInstanceOf(\SimpleXMLElement::class, $config->section);
        self::assertEquals('General', $config->section[0]['label']);
    }

    public function testValidInclusionNoSchemaStillPasses()
    {
        $file = new XmlFile(__DIR__ . '/xml/config-no-schema.xml');
        $config = $file->toXml();
        self::assertInstanceOf(\SimpleXMLElement::class, $config->section);
    }

}

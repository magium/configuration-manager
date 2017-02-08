<?php

namespace Magium\Configuration\Tests\Adapter\Context;


use Magium\Configuration\File\Context\XmlFile;
use PHPUnit\Framework\TestCase;

class XmlFileTest extends TestCase
{

    public function testContextNesting()
    {
        $file = new XmlFile(__DIR__ . '/xml/context.xml');
        $xml = $file->toXml();
        self::assertInstanceOf(\SimpleXMLElement::class, $xml);
        self::assertInstanceOf(\SimpleXMLElement::class, $xml->context);
        self::assertEquals('production', $xml->context['id']);

    }

}

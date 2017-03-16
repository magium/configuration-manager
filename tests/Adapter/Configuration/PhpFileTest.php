<?php

namespace Magium\Configuration\Tests\Adapter\Configuration;

use Magium\Configuration\File\InvalidFileException;
use Magium\Configuration\File\Configuration\PhpFile;
use PHPUnit\Framework\TestCase;

class PhpFileTest extends TestCase
{

    public function testInvalidFileThrowsException()
    {
        $this->expectException(InvalidFileException::class);
        new PhpFile('non-existent-file');
    }

    public function testValidInclusion()
    {
        $file = new PhpFile(__DIR__ . '/php/config.php');
        $config = $file->toXml();
        self::assertInstanceOf(\SimpleXMLElement::class, $config->section);
        self::assertEquals('General', $config->section[0]['label']);
    }

}

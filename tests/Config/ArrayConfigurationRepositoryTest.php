<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\InvalidArgumentException;
use Magium\Configuration\Config\Repository\ArrayConfigurationRepository;
use PHPUnit\Framework\TestCase;

class ArrayConfigurationRepositoryTest extends TestCase
{

    public function testXpath()
    {
        $obj = new ArrayConfigurationRepository([
            'one'   => [
                'two'   => [
                    'three' => 'value'
                ]
            ]
        ]);
        self::assertEquals('value', $obj->xpath('one/two/three'));
    }

    public function testNonArrayConstructorArgumentThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new ArrayConfigurationRepository('');
    }

    public function testOneNodeInvalidXpathThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new ArrayConfigurationRepository([]);
        $obj->xpath('justone');
    }

    public function testMoreThanThreeNodeInvalidXpathThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new ArrayConfigurationRepository([]);
        $obj->xpath('one/two/three/four');
    }

    public function testHasValue()
    {
        $obj = new ArrayConfigurationRepository([
            'one'   => [
                'two'   => [
                    'three' => 'value'
                ]
            ]
        ]);
        self::assertTrue($obj->getValueFlag('one/two/three'));
    }

    public function testHasFalseValue()
    {
        $obj = new ArrayConfigurationRepository([
            'one'   => [
                'two'   => [
                    'three' => 'value'
                ]
            ]
        ]);
        self::assertFalse($obj->getValueFlag('one/two/four'));
    }

    public function testGetValue()
    {
        $obj = new ArrayConfigurationRepository([
            'one'   => [
                'two'   => [
                    'three' => 'value'
                ]
            ]
        ]);
        self::assertEquals('value', $obj->getValue('one/two/three'));
    }

}

<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\Repository\IniConfigurationRepository;
use PHPUnit\Framework\TestCase;

class IniConfigurationRepositoryTest extends TestCase
{

    public function testXpath()
    {
        $ini = <<<YAML
[one]
two[three] = value
YAML;
        $obj = new IniConfigurationRepository(trim($ini));
        self::assertEquals('value', $obj->xpath('one/two/three'));
    }

}

<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\Repository\YamlConfigurationRepository;
use PHPUnit\Framework\TestCase;

class YamlConfigurationRepositoryTest extends TestCase
{

    public function testXpath()
    {
        $yaml = <<<YAML
one:
    two:
        three: value
YAML;
        $obj = new YamlConfigurationRepository(trim($yaml));
        self::assertEquals('value', $obj->xpath('one/two/three'));
    }

}

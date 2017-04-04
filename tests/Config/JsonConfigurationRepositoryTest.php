<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\Repository\JsonConfigurationRepository;
use PHPUnit\Framework\TestCase;

class JsonConfigurationRepositoryTest extends TestCase
{

    public function testXpath()
    {
        $json = <<<JSON
        {
            "one": {
                "two": {
                    "three": "value"
                }
            }
        }
JSON;
        $obj = new JsonConfigurationRepository(trim($json));
        self::assertEquals('value', $obj->xpath('one/two/three'));
    }

}

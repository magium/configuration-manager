<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\Repository\YamlConfigurationRepository;
use PHPUnit\Framework\TestCase;

class YamlConfigurationRepositoryTest extends TestCase
{
    protected function setUp()
    {
        if (!class_exists('Symfony\Component\Yaml\Yaml')) {
            $this->markTestSkipped(
                'The package symfony/yaml is not available.'
            );
        }
    }

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

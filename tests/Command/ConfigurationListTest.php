<?php

namespace Magium\Configuration\Tests\Command;

use Magium\Configuration\Console\Command\ConfigurationList;
use PHPUnit\Framework\TestCase;

class ConfigurationListTest extends TestCase
{

    public function testSlug()
    {
        $command = new ConfigurationList();
        self::assertEquals('magium:configuration:list', $command->getName());
    }

}

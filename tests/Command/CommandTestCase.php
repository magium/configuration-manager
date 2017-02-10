<?php

namespace Magium\Configuration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;

abstract class CommandTestCase extends TestCase
{

    protected function getApplication()
    {
        $application = new Application();
        return $application;
    }

}

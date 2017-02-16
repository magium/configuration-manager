<?php

namespace Magium\Configuration\Tests\Command\Symfony;

use Magium\Configuration\Console\Command\DefaultCommand;
use Magium\Configuration\Console\Symfony\CommandList;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class CommandListTest extends TestCase
{

    public function testThatAllCommandsAreIncluded()
    {
        $application = new Application();
        $commandList = new CommandList();
        $commandList->addCommands($application);
        $reflectionClass = new \ReflectionClass(DefaultCommand::class);
        $namespace = $reflectionClass->getNamespaceName();
        $dir = $reflectionClass->getFileName();
        $glob = dirname($dir) . DIRECTORY_SEPARATOR . '*.php';
        foreach (glob($glob) as $file) {
            $name = basename($file);
            $name = substr($name, 0,  -4);
            $class = sprintf('%s\%s', $namespace, $name);
            $object = new $class();
            if ($object instanceof Command) {
                $consoleName = $object->getName();
                $appInstance = $application->get($consoleName);
                self::assertInstanceOf($class, $appInstance);
            } else {
                self::fail('Command classes need to extend Command');
            }
        }
    }

}

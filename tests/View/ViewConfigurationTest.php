<?php

namespace Magium\Configuration\Tests\View;

use Magium\Configuration\View\InvalidViewConfigurationException;
use Magium\Configuration\View\ViewConfiguration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ServerRequestInterface;

class ViewConfigurationTest extends TestCase
{

    public function testValidViewDirectory()
    {
        $viewConfiguration = new ViewConfiguration(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(MessageInterface::class)
        );
        self::assertDirectoryExists($viewConfiguration->getViewDirectory());
    }

    public function testValidCustomerViewDirectory()
    {
        $viewConfiguration = new ViewConfiguration(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(MessageInterface::class),
            __DIR__ . '/view'
        );
        self::assertDirectoryExists($viewConfiguration->getViewDirectory());
    }

    public function testInValidViewDirectoryThrowsException()
    {
        $this->expectException(InvalidViewConfigurationException::class);
        new ViewConfiguration(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(MessageInterface::class),
            'boogers'
        );

    }

    public function testValidView()
    {
        $viewConfiguration = new ViewConfiguration(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(MessageInterface::class)
        );
        self::assertFileExists($viewConfiguration->getViewDirectory() . '/view.phtml');
    }

    public function testInValidLayoutThrowsException()
    {
        $this->expectException(InvalidViewConfigurationException::class);
        new ViewConfiguration(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(MessageInterface::class),
            __DIR__ . '/view',
            'invalid.phtml'
        );
    }

    public function testInValidViewThrowsException()
    {
        $this->expectException(InvalidViewConfigurationException::class);
        new ViewConfiguration(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(MessageInterface::class),
            __DIR__ . '/view',
            'view.phtml',
            'invalid.phtml'
        );
    }


}

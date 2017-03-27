<?php

namespace Magium\Configuration\Tests\Container;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Container\GenericContainer;
use Magium\Configuration\Container\InvalidObjectException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{

    /**
     * @var GenericContainer
     */

    protected $container;

    protected function setUp()
    {
        parent::setUp();
        $this->container = new GenericContainer();
    }

    public function testGetBasicObject()
    {
        $result = $this->container->get(ModelInjected::class);
        self::assertInstanceOf(ModelInjected::class, $result);
    }

    public function testObjectInterfaceIsRegistered()
    {
        $this->container->get(Model::class);
        self::assertInstanceOf(Model::class, $this->container->get(ModelInterface::class));
    }

    public function testInternalClassesNotRegistered()
    {
        $this->container->get(Model::class);
        self::assertFalse($this->container->has(\ArrayObject::class));
        self::assertFalse($this->container->has(\Countable::class));
    }

    public function testContainerRegistersItself()
    {
        $result = $this->container->get(ContainerInterface::class);
        self::assertInstanceOf(GenericContainer::class, $result);
    }

    public function testHasBasicObject()
    {
        $this->container->get(ModelInjected::class);
        self::assertTrue($this->container->has(ModelInjected::class));
    }

    public function testSetNonObjectThrowsException()
    {
        $this->expectException(InvalidObjectException::class);
        $this->container->set('somestring');
    }

    public function testBasicInjection()
    {
        $model = $this->container->get(Model::class);
        self::assertInstanceOf(Model::class, $model);
        self::assertInstanceOf(ModelInjected::class, $model->model);
    }

    public function testOptionalModel()
    {
        $model = $this->container->get(OptionalModel::class);
        self::assertInstanceOf(OptionalModel::class, $model);
        self::assertInstanceOf(ModelInjected::class, $model->model);
    }

    public function testNonOptionalStringParameterThrowsException()
    {
        $this->expectException(InvalidObjectException::class);
        $this->container->get(NonObjectRequiredModel::class);;
    }

    public function testOptionalStringParameterWorks()
    {
        $result = $this->container->get(NonObjectModel::class);
        self::assertNull($result->model);
    }
}

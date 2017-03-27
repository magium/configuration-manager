<?php

namespace Magium\Configuration\Tests\Container;

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
        $result = $this->container->get(\ArrayObject::class);
        self::assertInstanceOf(\ArrayObject::class, $result);
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

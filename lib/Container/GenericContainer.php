<?php

namespace Magium\Configuration\Container;

use Interop\Container\ContainerInterface;

class GenericContainer implements ContainerInterface
{

    protected $container = [];

    public function __construct(array $defaults = [])
    {
        $this->container = $defaults;
        $this->set($this);
    }

    public function set($value)
    {
        if (!is_object($value)) {
            throw new InvalidObjectException('The GenericContainer can only accept objects');
        }
        $class = new \ReflectionClass($value);
        $interfaces = $class->getInterfaces();
        foreach ($interfaces as $interface) {
            if (!$interface->isInternal()) {
                $this->container[$interface->getName()] = $value;
            }
        }
        do {
            if ($class->isInternal()) {
                return; // Our work is done here.
            }
            $this->container[$class->getName()] = $value;
        } while (($class = $class->getParentClass()) instanceof \ReflectionClass);
    }

    public function get($id)
    {
        if (!isset($this->container[$id])) {
            $this->set($this->newInstance($id));
        }
        return $this->container[$id];
    }

    public function newInstance($type)
    {
        $reflection = new \ReflectionClass($type);
        $constructor = $reflection->getConstructor();
        $constructorParams = $this->getParams($constructor);
        if ($constructorParams) {
            $requestedInstance = $reflection->newInstanceArgs($constructorParams);
        } else {
            $requestedInstance = $reflection->newInstance();
        }
        return $requestedInstance;
    }

    protected function getParams(\ReflectionMethod $method = null)
    {
        if (!$method instanceof \ReflectionMethod) {
            return [];
        }
        $constructorParams = [];
        $params = $method->getParameters();
        foreach ($params as $param) {
            if ($param->getClass() instanceof \ReflectionClass) {
                $class = $param->getClass()->getName();
                $instance = $this->get($class);
                $constructorParams[] = $instance;
            } else if (!$param->isOptional()) {
                throw new InvalidObjectException(
                    'The generic container will only manage constructor arguments that are objects'
                );
            }
        }
        return $constructorParams;
    }

    public function has($id)
    {
        return isset($this->container[$id]);
    }

}

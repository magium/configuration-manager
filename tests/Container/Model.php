<?php

namespace Magium\Configuration\Tests\Container;

class Model extends \ArrayObject implements ModelInterface, \Countable
{

    public $model;

    public function __construct(ModelInjected $model)
    {
        $this->model = $model;
    }

}

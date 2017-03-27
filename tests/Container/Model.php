<?php

namespace Magium\Configuration\Tests\Container;

class Model implements ModelInterface
{

    public $model;

    public function __construct(ModelInjected $model)
    {
        $this->model = $model;
    }

}

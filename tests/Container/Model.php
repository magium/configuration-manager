<?php

namespace Magium\Configuration\Tests\Container;

class Model
{

    public $model;

    public function __construct(ModelInjected $model)
    {
        $this->model = $model;
    }

}

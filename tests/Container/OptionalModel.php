<?php

namespace Magium\Configuration\Tests\Container;

class OptionalModel
{

    public $model;

    public function __construct(ModelInjected $injected = null)
    {
        $this->model = $injected;
    }

}

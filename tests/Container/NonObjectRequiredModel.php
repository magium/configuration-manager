<?php

namespace Magium\Configuration\Tests\Container;

class NonObjectRequiredModel
{

    public $model;

    public function __construct($injected)
    {
        $this->model = $injected;
    }

}

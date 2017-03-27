<?php

namespace Magium\Configuration\Tests\Container;

class NonObjectModel
{

    public $model;

    public function __construct($injected = null)
    {
        $this->model = $injected;
    }

}

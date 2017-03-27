<?php

namespace Magium\Configuration\Tests\Config;

use Magium\Configuration\Config\Storage\CallbackInterface;

class InsufficientCallback implements CallbackInterface
{

    public function __construct($stringParam)
    {
    }

    public function filter($value)
    {
        return strtoupper($value);
    }
}

<?php

namespace Magium\Configuration\Tests\Config;

class Callback
{

    public function strToUpper($value)
    {
        return strtoupper($value);
    }

}
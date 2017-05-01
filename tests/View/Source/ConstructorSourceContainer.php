<?php

namespace Magium\Configuration\Tests\View\Source;

use Interop\Container\ContainerInterface;

class ConstructorSourceContainer implements ContainerInterface
{
    public function get($id)
    {
        return new ConstructorSource([
            'a' => 'a'
        ]);
    }

    public function has($id)
    {
        return true;
    }


}

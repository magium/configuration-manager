<?php

namespace Magium\Configuration\Tests\View\Source;

use Magium\Configuration\Source\SourceInterface;

class ConstructorSource implements SourceInterface
{

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getSourceData()
    {
        return $this->data;
    }
}

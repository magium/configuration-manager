<?php

namespace Magium\Configuration\Source\Switches;

use Magium\Configuration\Source\SourceInterface;

class YesNo implements SourceInterface
{

    public function getSourceData()
    {
        return [
            0   => 'No',
            1   => 'Yes',
        ];
    }

}

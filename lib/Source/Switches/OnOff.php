<?php

namespace Magium\Configuration\Source\Switches;

use Magium\Configuration\Source\SourceInterface;

class OnOff implements SourceInterface
{

    public function getSourceData()
    {
        return [
            0   => 'Off',
            1   => 'On',
        ];
    }

}

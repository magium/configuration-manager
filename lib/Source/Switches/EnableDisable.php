<?php

namespace Magium\Configuration\Source\Switches;

use Magium\Configuration\Source\SourceInterface;

class EnableDisable implements SourceInterface
{

    public function getSourceData()
    {
        return [
            0   => 'Disable',
            1   => 'Enable',
        ];
    }

}

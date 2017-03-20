<?php

namespace Magium\Configuration\Source\Datetime;

use Magium\Configuration\Source\SourceInterface;

class NextTenYears implements SourceInterface
{

    public function getSourceData()
    {
        $now = date('Y');
        $return = [];
        do {
            $return[] = $now++;
        } while (count($return) < 10);
        return $return;

    }

}

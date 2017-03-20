<?php

namespace Magium\Configuration\Source\Datetime;

use Magium\Configuration\Source\SourceInterface;

class Months implements SourceInterface
{

    public function getSourceData()
    {
        return [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
    }

}

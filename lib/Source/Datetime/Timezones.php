<?php

namespace Magium\Configuration\Source\Datetime;

use Magium\Configuration\Source\SourceInterface;

class Timezones implements SourceInterface
{

    public function getSourceData()
    {
        return \DateTimeZone::listIdentifiers();
    }

}

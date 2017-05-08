<?php

namespace Magium\Configuration\Source\Switches;

use Magium\Configuration\Source\SourceInterface;

class HttpHttps implements SourceInterface
{

    public function getSourceData()
    {
        return [
            'http'   => 'HTTP',
            'https'   => 'HTTPS',
        ];
    }

}

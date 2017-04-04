<?php

namespace Magium\Configuration\Config\Repository;

class IniConfigurationRepository extends ArrayConfigurationRepository
{

    public function __construct($data)
    {
        $data = parse_ini_string($data, true);
        parent::__construct($data);
    }

}

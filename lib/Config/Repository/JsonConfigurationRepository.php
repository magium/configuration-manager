<?php

namespace Magium\Configuration\Config\Repository;

class JsonConfigurationRepository extends ArrayConfigurationRepository
{

    public function __construct($data)
    {
        $data = json_decode($data, true);
        parent::__construct($data);
    }

}

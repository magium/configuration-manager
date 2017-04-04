<?php

namespace Magium\Configuration\Config\Repository;

use Symfony\Component\Yaml\Yaml;

class YamlConfigurationRepository extends ArrayConfigurationRepository
{

    public function __construct($data)
    {
        $data = Yaml::parse($data);
        parent::__construct($data);
    }

}

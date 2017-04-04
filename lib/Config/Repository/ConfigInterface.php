<?php

namespace Magium\Configuration\Config\Repository;

interface ConfigInterface
{

     const ALLOWED_TRUES = [
        true, 'true', 1, '1', 'on', 'yes'
    ];

    public function getValue($path);

    public function hasValue($path);

    public function getValueFlag($path);

    public function xpath($xpath);

}

<?php

namespace Magium\Configuration\Config\Repository;

interface ConfigInterface
{
    public function getValue($path);

    public function hasValue($path);

    public function getValueFlag($path);

    public function xpath($xpath);

}

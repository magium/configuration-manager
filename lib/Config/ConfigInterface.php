<?php

namespace Magium\Configuration\Config;

interface ConfigInterface
{
    public function getValue($path);

    public function getValueFlag($path);
}

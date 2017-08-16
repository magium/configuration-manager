<?php

namespace Magium\Configuration\Console\Command;

use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    const MAGIUM_PREFIX = 'magium:configuration:';

    private $useShort;

    public function __construct($name = null, $useShort = false)
    {
        $this->useShort = $useShort;
        parent::__construct($name);
    }

    public function setName($name)
    {
        if ($this->useShort) {
            $name = substr($name, strlen(self::MAGIUM_PREFIX));
        }
        return parent::setName($name);
    }

}

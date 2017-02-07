<?php

namespace Magium\Configuration\Config\Storage;

use Magium\Configuration\Config\Config;

interface StorageInterface
{

    public function getValue($path, $context = Config::CONTEXT_DEFAULT);

    public function setValue($path, $value, $context = Config::CONTEXT_DEFAULT);

    public function create();

}
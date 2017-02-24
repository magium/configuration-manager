<?php

namespace Magium\Configuration\Config\Storage;

use Magium\Configuration\Config\ConfigurationRepository;

interface StorageInterface
{

    public function getValue($path, $context = ConfigurationRepository::CONTEXT_DEFAULT);

    public function setValue($path, $value, $context = ConfigurationRepository::CONTEXT_DEFAULT);

    public function create();

}

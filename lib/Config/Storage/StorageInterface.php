<?php

namespace Magium\Configuration\Config\Storage;

interface StorageInterface
{

    public function getValue($location, $context);

}
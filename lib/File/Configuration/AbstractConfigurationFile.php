<?php

namespace Magium\Configuration\File\Configuration;

use Magium\Configuration\File\AbstractAdapter;

abstract class AbstractConfigurationFile extends AbstractAdapter
{
    protected function configureSchema(\DOMElement $element)
    {
        $schema = realpath(__DIR__ . '/../../../assets/configuration.xsd');
        $element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.magiumlib.com/Configuration');
        $element->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://www.magiumlib.com/Configuration ' . $schema);
        return $schema;
    }
}

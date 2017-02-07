<?php

namespace Magium\Configuration\File\Context;

use Magium\Configuration\File\AbstractAdapter;

abstract class AbstractContextConfigurationFile extends AbstractAdapter
{
    protected function configureSchema(\DOMElement $element)
    {
        $schema = realpath(__DIR__ . '/../../../assets/context.xsd');
        $element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.magiumlib.com/ConfigurationContext');
        $element->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://www.magiumlib.com/ConfigurationContext ' . $schema);
        return $schema;
    }
}

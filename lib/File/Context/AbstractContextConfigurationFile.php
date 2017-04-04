<?php

namespace Magium\Configuration\File\Context;

use Magium\Configuration\Config\Repository\ConfigurationRepository;
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

    public function getContexts()
    {
        $contexts = [ConfigurationRepository::CONTEXT_DEFAULT];
        $xml = $this->toXml();
        $xml->registerXPathNamespace('s', 'http://www.magiumlib.com/ConfigurationContext');
        $configuredContexts = $xml->xpath('//s:context');
        foreach ($configuredContexts as $context) {
            $contexts[] = (string)$context['id'];
        }
        return $contexts;
    }
}

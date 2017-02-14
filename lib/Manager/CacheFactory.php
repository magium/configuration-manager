<?php

namespace Magium\Configuration\Manager;

use Zend\Cache\StorageFactory;

class CacheFactory
{

    public function getCache(\SimpleXMLElement $element)
    {
        $config = [
            'adapter'   => (string)$element->adapter,
            'options'   => []
        ];
        if (isset($element->options)) {
            foreach ($element->options->children() as $value) {
                if ($value instanceof \SimpleXMLElement) {
                    $config['options'][$value->getName()] = (string)$value;
                }
            }
        }
        $cache = StorageFactory::factory($config);
        return $cache;
    }

}

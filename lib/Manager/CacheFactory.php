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
            $options = json_encode($element->options);
            $config['options'] = json_decode($options, true);
        }
        $cache = StorageFactory::factory($config);
        return $cache;
    }

}

<?php

namespace Magium\Configuration;

use Magium\Configuration\Manager\Manager;
use Zend\Cache\StorageFactory;

class MagiumConfigurationFactory
{
    protected $file;
    protected $xml;

    protected $manager;

    public function __construct($magiumConfigurationFile = null)
    {
        if (!$magiumConfigurationFile) {
            $cwd = __DIR__;
            $baseDir = realpath(DIRECTORY_SEPARATOR);
            while ($cwd && $cwd != $baseDir && file_exists($cwd)) {
                $checkFile = $cwd . DIRECTORY_SEPARATOR . 'magium-configuration.xml';
                if (file_exists($checkFile)) {
                    $magiumConfigurationFile = $checkFile;
                    break;
                }
                $lastPos = strrpos($cwd, DIRECTORY_SEPARATOR);
                $cwd = substr($cwd, 0, $lastPos);
            }
        }

        if (file_exists($magiumConfigurationFile)) {
            $this->file = realpath($magiumConfigurationFile);
        } else {
            throw new InvalidConfigurationFileException('Unable to file configuration file: ' . $magiumConfigurationFile);
        }
        $this->xml = simplexml_load_file($magiumConfigurationFile);
    }

    public function validateConfigurationFile()
    {
        $result = false;
        try {
            $doc = new \DOMDocument();
            $doc->load($this->file);
            $result = $doc->schemaValidate(__DIR__ . '/../assets/magium-configuration.xsd');
        } catch (\Exception $e) {
            // $result value is already set
        }
        return $result;
    }

    protected function getCache(\SimpleXMLElement $element)
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

    public function getManager()
    {
        if (!$this->manager instanceof Manager) {
            $cacheConfig = $this->xml->cache;
            $globalAdapter = $this->getCache($cacheConfig);
            $localCache = null;
            $localCacheConfig = $this->xml->localCache;
            if ($localCacheConfig) {
                $localCache = $this->getCache($localCacheConfig);
            }
            $this->manager = new Manager($globalAdapter, $localCache);
        }
        return $this->manager;
    }

}

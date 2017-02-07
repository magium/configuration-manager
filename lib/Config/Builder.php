<?php

namespace Magium\Configuration\Config;

use Magium\Configuration\Config\Storage\StorageInterface as ConfigurationStorageInterface;
use Magium\Configuration\File\AdapterInterface;
use Magium\Configuration\File\InvalidFileException;
use Zend\Cache\Storage\StorageInterface;

class Builder
{

    protected $files = [];

    protected $secureBases = [];
    protected $cache;
    protected $hashAlgo;
    protected $storage;

    public function __construct(
        StorageInterface $cache,
        ConfigurationStorageInterface $storage,
        array $secureBases = [],
        $hashAlgo = 'sha1'
    )
    {
        $this->cache = $cache;
        $this->storage = $storage;
        $this->hashAlgo = $hashAlgo;
        $this->storage = $storage;

        foreach ($secureBases as $base) {
            $this->addSecureBase($base);
        }
    }


    public function registerConfigurationFile(AdapterInterface $file)
    {
        $this->files[] = $file;
    }

    public function addSecureBase($base)
    {
        $path = realpath($base);
        if (!is_dir($path)) {
            throw new InvalidDirectoryException('Unable to determine real path for directory: ' . $base);
        }
        $this->secureBases[] = $path;
    }

    /**
     * @param Config|null $config
     * @return Config
     * @throws InvalidConfigurationLocationException
     * @throws InvalidFileException
     */

    public function build($context = Config::CONTEXT_DEFAULT, Config $config = null)
    {
        if (!$this->files) {
            throw new MissingConfigurationException('No configuration files have been provided.  Please add via registerConfigurationFile()');
        }

        if (!$config instanceof Config) {
            $config = new Config('<config />');
        }
        $structure = null;
        foreach ($this->files as $file) {
            if (!$file instanceof AdapterInterface) {
                throw new InvalidFileException('Configuration file object must implement ' . AdapterInterface::class);
            }
            $path = $file->getFile();
            $path = realpath($path);
            $inSecurePath = false;
            foreach ($this->secureBases as $base) {
                if (strpos($path, $base) === 0) {
                    $inSecurePath = true;
                    break;
                }
            }

            if (!$inSecurePath) {
                throw new InvalidConfigurationLocationException($path . ' is not in one of the designated secure configuration paths.');
            }
            $simpleXml = $file->toXml();
            if (!$structure instanceof \SimpleXMLElement) {
                $structure = $simpleXml;
            } else {
                $this->mergeStructure($structure, $simpleXml);
            }
        }

        $this->buildConfigurationObject($structure, $config, $context);

        $hash = hash_hmac($this->hashAlgo, $config->asXML(), '');

        return $config;
    }

    /**
     * @param \SimpleXMLElement $structure The object representing the merged configuration structure
     * @param \SimpleXmlElement $config An empty config object to be populated
     */

    public function buildConfigurationObject(\SimpleXMLElement $structure, Config $config, $context = Config::CONTEXT_DEFAULT)
    {
        $structure->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $paths = $structure->xpath('/*/s:section/s:group/s:element');
        foreach ($paths as $path) {
            if ($path instanceof \SimpleXMLElement) {
                $elementId = $path['id'];
                $group = $path->xpath('..')[0];
                $groupId = $group['id'];
                $section = $group->xpath('..')[0];
                $sectionId = $section['id'];
                $configPath = sprintf('%s/%s/%s', $sectionId, $groupId, $elementId);
                $value = $this->storage->getValue($configPath, $context);
                if (!$value) {
                    $xpath = sprintf('/*/s:section[@id="%s"]/s:group[@id="%s"]/s:element[@id="%s"]/s:value',
                        $sectionId,
                        $groupId,
                        $elementId
                    );
                    $result = $structure->xpath($xpath);
                    if ($result) {
                        $value = trim((string)$result[0]);
                    }
                }

                if ($value) {
                    $config->$sectionId->$groupId->$elementId = $value;
                }
            }
        }
    }

    public function mergeStructure(\SimpleXMLElement $base, \SimpleXMLElement $new)
    {
        $base->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        foreach ($new as $item) {
            if ($item instanceof \SimpleXMLElement) {
                $xpath = sprintf('/*/s:section[@id="%s"]', $item['id']);
                $sectionExists = $base->xpath($xpath);
                $section = null;
                if ($sectionExists && $sectionExists[0] instanceof \SimpleXMLElement) {
                    $section = $sectionExists[0];
                } else {
                    $section = $base->addChild('section');
                }

                foreach ($item->attributes() as $name => $value) {
                    $section[$name] = $value;
                }
                if ($item->group) {
                    $this->mergeGroup($section, $item->group);
                }
            }
        }
    }

    protected function mergeGroup(\SimpleXMLElement $section, \SimpleXMLElement $newGroups)
    {
        $section->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        foreach ($newGroups as $newGroup) {
            if ($newGroup instanceof \SimpleXMLElement) {
                $xpath = sprintf('./s:group[@id="%s"]', $newGroup['id']);
                $groupExists = $section->xpath($xpath);
                $group = null;
                if ($groupExists && $groupExists[0] instanceof \SimpleXMLElement) {
                    $group = $groupExists[0];
                } else {
                    $group = $section->addChild('group');
                }
                foreach ($newGroup->attributes() as $name => $value) {
                    $group[$name] = $value;
                }
                $this->mergeElements($group, $newGroup->element);
            }
        }
    }

    protected function mergeElements(\SimpleXMLElement $group, \SimpleXMLElement $newElements)
    {
        $group->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        foreach ($newElements as $newElement) {
            if ($newElement instanceof \SimpleXMLElement) {
                $xpath = sprintf('./s:element[@id="%s"]', $newElement['id']);
                $elementExists = $group->xpath($xpath);
                $element = null;
                if ($elementExists && $elementExists[0] instanceof \SimpleXMLElement) {
                    $element = $elementExists[0];
                } else {
                    $element = $group->addChild('element');
                }
                foreach ($newElement->attributes() as $name => $value) {
                    $element[$name] = $value;
                }
                foreach ($newElement->children() as $key => $item) {
                    $element->$key = $item;
                }
            }
        }
    }

}
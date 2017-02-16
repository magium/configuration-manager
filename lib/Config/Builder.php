<?php

namespace Magium\Configuration\Config;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\Storage\StorageInterface as ConfigurationStorageInterface;
use Magium\Configuration\File\AdapterInterface;
use Magium\Configuration\File\Configuration\ConfigurationFileRepository;
use Magium\Configuration\File\Configuration\UnsupportedFileTypeException;
use Magium\Configuration\File\InvalidFileException;
use Magium\Configuration\InvalidConfigurationException;
use Zend\Cache\Storage\StorageInterface;

class Builder implements BuilderInterface
{

    protected $repository;
    protected $cache;
    protected $container;
    protected $hashAlgo;
    protected $storage;

    public function __construct(
        StorageInterface $cache,
        ConfigurationStorageInterface $storage,
        ConfigurationFileRepository $repository,
        ContainerInterface $container = null,
        $hashAlgo = 'sha1'
    )
    {
        $this->cache = $cache;
        $this->storage = $storage;
        $this->hashAlgo = $hashAlgo;
        $this->repository = $repository;
        $this->container = $container;
    }

    public function getConfigurationRepository()
    {
        return $this->repository;
    }

    public function getContainer()
    {
        if (!$this->container instanceof ContainerInterface) {
            throw new MissingContainerException(
                'You are using functionality that requires either a DI Container or Service Locator.  '
                . 'The container, or container adapter, must implement Interop\Container\ContainerInterface'
            );
        }
        return $this->container;
    }

    /**
     * Retrieves a list of files that have been registered
     *
     * @return ConfigurationFileRepository
     */

    public function getRegisteredConfigurationFiles()
    {
        return $this->repository;
    }

    /**
     * @param Config|null $config
     * @return Config
     * @throws InvalidConfigurationLocationException
     * @throws InvalidFileException
     * @throws
     */

    public function build($context = Config::CONTEXT_DEFAULT, ConfigInterface $config = null)
    {
        $files = $this->getRegisteredConfigurationFiles();
        if (!count($files)) {
            throw new MissingConfigurationException('No configuration files have been provided.  Please add via registerConfigurationFile()');
        }

        if (!$config instanceof Config) {
            $config = new Config('<config />');
        }
        $structure = null;
        foreach ($files as $file) {
            if (!$file instanceof AdapterInterface) {
                throw new InvalidFileException('Configuration file object must implement ' . AdapterInterface::class);
            }

            $simpleXml = $file->toXml();
            if (!$structure instanceof \SimpleXMLElement) {
                $structure = $simpleXml;
            } else {
                $this->mergeStructure($structure, $simpleXml);
            }
        }

        if (!$structure instanceof \SimpleXMLElement) {
            throw new InvalidConfigurationException('No configuration files provided');
        }

        $this->buildConfigurationObject($structure, $config, $context);

        return $config;
    }

    /**
     * @param \SimpleXMLElement $structure The object representing the merged configuration structure
     * @param \SimpleXmlElement $config An empty config object to be populated
     * @return Config The resulting configuration object
     */

    public function buildConfigurationObject(
        \SimpleXMLElement $structure,
        ConfigInterface $config,
        $context = Config::CONTEXT_DEFAULT
    )
    {
        $structure->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $elements = $structure->xpath('/*/s:section/s:group/s:element');
        foreach ($elements as $element) {
            if ($element instanceof \SimpleXMLElement) {
                $elementId = $element['id'];
                $group = $element->xpath('..')[0];
                $groupId = $group['id'];
                $section = $group->xpath('..')[0];
                $sectionId = $section['id'];
                $configPath = sprintf('%s/%s/%s', $sectionId, $groupId, $elementId);
                $value = $this->storage->getValue($configPath, $context);
                if ($value) {
                    if (isset($element['callbackFromStorage'])) {
                        $callbackString = (string)$element['callbackFromStorage'];
                        $callback = explode('::', $callbackString);
                        if (count($callback) == 2) {
                            $callback[0] = $this->getContainer()->get($element['callbackFromStorage']);
                        } else {
                            $callback = array_shift($callback);
                        }
                        if (!is_callable($callback)) {
                            throw new UncallableCallbackException('Unable to execute callback: ' . $callbackString);
                        }
                        $value = call_user_func($callback, $value);
                    }
                } else {
                    $xpath = sprintf('/*/s:section[@id="%s"]/s:group[@id="%s"]/s:element[@id="%s"]/s:value',
                        $sectionId,
                        $groupId,
                        $elementId
                    );
                    $result = $structure->xpath($xpath);
                    if (!empty($result)) {
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

                if (!empty($sectionExists) && $sectionExists[0] instanceof \SimpleXMLElement) {
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

                if (!empty($groupExists) && $groupExists[0] instanceof \SimpleXMLElement) {
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

                if (!empty($elementExists) && $elementExists[0] instanceof \SimpleXMLElement) {
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

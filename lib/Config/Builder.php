<?php

namespace Magium\Configuration\Config;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\Storage\StorageInterface as ConfigurationStorageInterface;
use Magium\Configuration\File\AdapterInterface;
use Magium\Configuration\File\Configuration\UnsupportedFileTypeException;
use Magium\Configuration\File\InvalidFileException;
use Zend\Cache\Storage\StorageInterface;

class Builder
{

    protected $files = [];

    protected $secureBases = [];
    protected $cache;
    protected $container;
    protected $hashAlgo;
    protected $storage;

    public function __construct(
        StorageInterface $cache,
        ConfigurationStorageInterface $storage,
        array $secureBases = [],
        ContainerInterface $container = null,
        array $configurationFiles = [],
        $hashAlgo = 'sha1'
    )
    {
        $this->cache = $cache;
        $this->storage = $storage;
        $this->hashAlgo = $hashAlgo;
        $this->storage = $storage;
        $this->container = $container;

        foreach ($secureBases as $base) {
            $this->addSecureBase($base);
        }

        $supportedTypes = [];
        if ($configurationFiles) {
            $checkSupportedTypes = glob(__DIR__ . '/../File/Configuration/*.php');
            foreach ($checkSupportedTypes as $file) {
                $file = basename($file);
                if ($file != 'AbstractConfigurationFile.php') {
                    $match = null;
                    if (preg_match('/^([a-zA-Z]+)File.php$/', $file, $match)) {
                        $supportedTypes[] = strtolower($match[1]);
                    }
                }
            }
        }

        foreach ($configurationFiles as $file) {
            $fileName = basename($file);
            $typeFound = false;
            foreach ($supportedTypes as $type) {
                if (strpos($fileName, '.' . $type) !== false) {
                    $class = 'Magium\Configuration\File\Configuration\\' . ucfirst($type) . 'File';
                    $configurationFile = new $class($file);
                    $this->registerConfigurationFile($configurationFile);
                    $typeFound = true;
                }
            }
            if (!$typeFound) {
                throw new UnsupportedFileTypeException(
                    sprintf(
                        'File %s does not have a supported file extension: %s',
                        $file,
                        implode(',', $supportedTypes)
                    ))
                ;
            }
        }
    }

    public function getContainer()
    {
        if (!$this->container instanceof ContainerInterface) {
            throw new MissingContainerException('You are using functionality that requires either a DI Container or Service Locator');
        }
        return $this->container;
    }

    public function registerConfigurationFile(AdapterInterface $file)
    {
        $this->files[] = $file;
    }

    /**
     * Retrieves a list of secure base directories
     *
     * @return array
     */

    public function getSecureBases()
    {
        return $this->secureBases;
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
     * Retrieves a list of files that have been registered
     *
     * @return array
     */

    public function getRegisteredConfigurationFiles()
    {
        return $this->files;
    }

    /**
     * @param Config|null $config
     * @return Config
     * @throws InvalidConfigurationLocationException
     * @throws InvalidFileException
     */

    public function build($context = Config::CONTEXT_DEFAULT, Config $config = null)
    {
        $files = $this->getRegisteredConfigurationFiles();
        if (!$files) {
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

        return $config;
    }

    /**
     * @param \SimpleXMLElement $structure The object representing the merged configuration structure
     * @param \SimpleXmlElement $config An empty config object to be populated
     * @return Config The resulting configuration object
     */

    public function buildConfigurationObject(\SimpleXMLElement $structure, Config $config, $context = Config::CONTEXT_DEFAULT)
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

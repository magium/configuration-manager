<?php

namespace Magium\Configuration\Config;

use Interop\Container\ContainerInterface;
use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\Storage\CallbackInterface;
use Magium\Configuration\Config\Storage\StorageInterface as ConfigurationStorageInterface;
use Magium\Configuration\Container\GenericContainer;
use Magium\Configuration\File\AdapterInterface;
use Magium\Configuration\File\Configuration\ConfigurationFileRepository;
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

    public function getStorage()
    {
        return $this->storage;
    }

    public function setValue($path, $value, $requestedContext = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        $parts = explode('/', $path);
        if (count($parts) != 3) {
            throw new InvalidArgumentException('Path must be in the structure of section/group/element');
        }

        $merged = $this->getMergedStructure();
        $merged->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');

        $xpath = sprintf('//s:section[@identifier="%s"]/s:group[@identifier="%s"]/s:element[@identifier="%s"]/s:permittedValues/s:value',
            $parts[0],
            $parts[1],
            $parts[2]
        );

        $elements = $merged->xpath($xpath);
        if ($elements) {
            $check = [];
            foreach ($elements as $element) {
                $check[] = (string)$element;
            }
            if (!in_array($value, $check)) {
                throw new InvalidArgumentException('The value must be one of: ' . implode(', ', $check));
            }
        }

        return $this->getStorage()->setValue($path, $value, $requestedContext);
    }

    public function getContainer()
    {
        if (!$this->container instanceof ContainerInterface) {
            $this->container = new GenericContainer();
            $this->container->set($this);
            $this->container->set($this->cache);
            $this->container->set($this->repository);
            $this->container->set($this->storage);
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
     * @param ConfigurationRepository|null $config
     * @return ConfigurationRepository
     * @throws InvalidConfigurationLocationException
     * @throws InvalidFileException
     * @throws
     */

    public function build($context = ConfigurationRepository::CONTEXT_DEFAULT, ConfigInterface $config = null)
    {

        if (!$config instanceof ConfigurationRepository) {
            $config = new ConfigurationRepository('<config />');
        }

        $structure = $this->getMergedStructure();

        if (!$structure instanceof MergedStructure) {
            throw new InvalidConfigurationException('No configuration files provided');
        }

        $container = $this->getContainer();
        if ($container instanceof GenericContainer) {
            $container->set($config);
            $container->set($structure);
        }

        $this->buildConfigurationObject($structure, $config, $context);

        return $config;
    }

    /**
     * @return \SimpleXMLElement
     * @throws InvalidFileException
     * @throws MissingConfigurationException
     */

    public function getMergedStructure()
    {
        $files = $this->getRegisteredConfigurationFiles();
        if (!count($files)) {
            throw new MissingConfigurationException('No configuration files have been provided.  Please add via registerConfigurationFile()');
        }

        $structure = null;
        foreach ($files as $file) {
            if (!$file instanceof AdapterInterface) {
                throw new InvalidFileException('Configuration file object must implement ' . AdapterInterface::class);
            }

            $simpleXml = $file->toXml();
            if (!$structure instanceof MergedStructure) {
                $structure = $simpleXml;
            } else {
                $this->mergeStructure($structure, $simpleXml);
            }
        }
        return $structure;
    }

    protected function executeCallback($callbackString, $value)
    {
        if (function_exists($callbackString)) {
            $execute = $callbackString;
        } else {
            $container = $this->getContainer();
            try {
                $callbackObject = $this->getContainer()->get($callbackString);
                if (!$callbackObject instanceof CallbackInterface) {
                    throw new UncallableCallbackException('Callback must implement ' . CallbackInterface::class);
                }
                $execute = [
                    $callbackObject,
                    'filter'
                ];

                if (!is_callable($execute)) {
                    throw new UncallableCallbackException('Unable to execute callback: ' . $callbackString);
                }
            } catch (\Exception $e) {
                /*
                 * This is slightly a hack but the purpose is to throw the insufficient exception if it's an actual
                 * problem with the generic container we provide.
                 */
                if ($container instanceof GenericContainer && !$e instanceof UncallableCallbackException) {
                    throw new InsufficientContainerException(
                        'You are using functionality that requires either a fully functional DI Container or Service Locator.  '
                        . 'The container, or container adapter, must implement Interop\Container\ContainerInterface.'
                    );
                }
                throw $e;
            }
        }

        return call_user_func($execute, $value);
    }


    /**
     * @param \SimpleXMLElement $structure The object representing the merged configuration structure
     * @param \SimpleXmlElement $config An empty config object to be populated
     * @param string $context The name of the context
     * @return ConfigurationRepository The resulting configuration object
     */

    public function buildConfigurationObject(
        MergedStructure $structure,
        ConfigInterface $config,
        $context = ConfigurationRepository::CONTEXT_DEFAULT
    )
    {
        $structure->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        $elements = $structure->xpath('/*/s:section/s:group/s:element');
        foreach ($elements as $element) {
            if ($element instanceof MergedStructure) {
                $elementId = $element['identifier'];
                $group = $element->xpath('..')[0];
                $groupId = $group['identifier'];
                $section = $group->xpath('..')[0];
                $sectionId = $section['identifier'];
                $configPath = sprintf('%s/%s/%s', $sectionId, $groupId, $elementId);
                $value = $this->storage->getValue($configPath, $context);
                if (!$value) {
                    $xpath = sprintf('/*/s:section[@identifier="%s"]/s:group[@identifier="%s"]/s:element[@identifier="%s"]/s:value',
                        $sectionId,
                        $groupId,
                        $elementId
                    );
                    $result = $structure->xpath($xpath);
                    if (!empty($result)) {
                        $value = trim((string)$result[0]);
                    }
                }
                if (isset($element['callbackFromStorage'])) {
                    $callbackString = (string)$element['callbackFromStorage'];

                    $value = $this->executeCallback($callbackString, $value);
                }

                if ($value) {
                    $config->$sectionId->$groupId->$elementId = $value;
                }
            }
        }
    }

    public function mergeStructure(MergedStructure $base, \SimpleXMLElement $new)
    {
        $base->registerXPathNamespace('s', 'http://www.magiumlib.com/Configuration');
        foreach ($new as $item) {
            if ($item instanceof \SimpleXMLElement) {
                $xpath = sprintf('/*/s:section[@identifier="%s"]', $item['identifier']);
                $sectionExists = $base->xpath($xpath);

                if (!empty($sectionExists) && $sectionExists[0] instanceof \SimpleXMLElement) {
                    $section = $sectionExists[0];
                } else {
                    $section = $base->addChild('section');
                }

                foreach ($item->attributes() as $name => $value) {
                    $section[$name] = (string)$value;
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
                $xpath = sprintf('./s:group[@identifier="%s"]', $newGroup['identifier']);
                $groupExists = $section->xpath($xpath);

                if (!empty($groupExists) && $groupExists[0] instanceof \SimpleXMLElement) {
                    $group = $groupExists[0];
                } else {
                    $group = $section->addChild('group');
                }
                foreach ($newGroup->attributes() as $name => $value) {
                    $group[$name] = (string)$value;
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
                $xpath = sprintf('./s:element[@identifier="%s"]', $newElement['identifier']);
                $elementExists = $group->xpath($xpath);

                if (!empty($elementExists) && $elementExists[0] instanceof \SimpleXMLElement) {
                    $element = $elementExists[0];
                } else {
                    $element = $group->addChild('element');
                }
                foreach ($newElement->attributes() as $name => $value) {
                    $element[$name] = (string)$value;
                }
                $this->mergeAllChildren($element, $newElement);
            }
        }
    }

    protected function mergeAllChildren(\SimpleXMLElement $destination, \SimpleXMLElement $source)
    {
        foreach ($source->attributes() as $name => $value) {
            $destination[$name] = (string)$value;
        }
        foreach ($source->children() as $key => $item) {
            $childNodes = $destination->xpath(sprintf('/%s', $key));
            if (!empty($childNodes) && $childNodes[0] instanceof \SimpleXMLElement) {
                $element = $childNodes[0];
            } else {
                $value = trim((string)$item);
                $element = $destination->addChild($key, $value);
            }
            $this->mergeAllChildren($element, $item);

        }
    }

}

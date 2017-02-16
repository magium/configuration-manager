<?php

namespace Magium\Configuration;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\BuilderFactory;
use Magium\Configuration\Config\BuilderFactoryInterface;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\InvalidConfigurationLocationException;
use Magium\Configuration\Config\MissingConfigurationException;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\Manager\CacheFactory;
use Magium\Configuration\Manager\Manager;
use Magium\Configuration\Manager\ManagerInterface;

class MagiumConfigurationFactory implements MagiumConfigurationFactoryInterface
{
    protected $file;
    protected $xml;

    protected $manager;
    protected $builder;
    protected $baseDir;
    protected $contextFile;
    protected $builderFactory;

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
        $this->baseDir = dirname($this->file);
        chdir($this->baseDir);
        $this->xml = simplexml_load_file($magiumConfigurationFile);
    }

    protected function buildContextFile()
    {
        chdir($this->baseDir);
        $contextFileCheck = (string)$this->xml->contextConfigurationFile['file'];
        $contextFileType = (string)$this->xml->contextConfigurationFile['type'];
        $contextFile = realpath($contextFileCheck);
        if (!$contextFile) {
            throw new MissingConfigurationException('Unable to find context file: ' . $contextFileCheck);
        }
        $class = 'Magium\Configuration\File\Context\\' . ucfirst($contextFileType) . 'File';
        $reflectionClass = new \ReflectionClass($class);
        if ($reflectionClass->isSubclassOf(AbstractContextConfigurationFile::class)) {
            $instance = $reflectionClass->newInstance($contextFile);
            if ($instance instanceof AbstractContextConfigurationFile) {
                return $instance;
            }
        }
        throw new InvalidConfigurationException('Unable to load context configuration file: ' . $contextFileCheck);
    }

    public function getContextFile()
    {
        if (!$this->contextFile instanceof AbstractContextConfigurationFile) {
            $this->contextFile = $this->buildContextFile();
        }
        return $this->contextFile;
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

    /**
     * Retrieves an instance of the cache based off of the XML cache configuration
     *
     * @param \SimpleXMLElement $element
     * @return \Zend\Cache\Storage\StorageInterface
     */

    protected function getCache(\SimpleXMLElement $element)
    {
        $cacheFactory = new CacheFactory();
        return $cacheFactory->getCache($element);
    }

    public function getBuilderFactory()
    {
        if (!$this->builderFactory instanceof BuilderFactoryInterface) {
            $builderFactoryConfig = $this->xml->builderFactory;
            $class = (string)$builderFactoryConfig['class'];
            if (!$class) {
                $class = BuilderFactory::class; // das default
            }
            $reflection = new \ReflectionClass($class);
            if (!$reflection->implementsInterface(BuilderFactoryInterface::class)) {
                throw new InvalidConfigurationException($class . ' must implement ' . BuilderFactoryInterface::class);
            }
            $this->builderFactory = $reflection->newInstance($this->xml);
        }
        return $this->builderFactory;
    }

    /**
     * @return BuilderInterface
     */

    public function getBuilder()
    {
        if (!$this->builder instanceof BuilderInterface) {
            $this->builder = $this->getBuilderFactory()->getBuilder();
        }
        return $this->builder;
    }

    protected function getRemoteCache()
    {
        $cacheConfig = $this->xml->cache;
        $globalAdapter = $this->getCache($cacheConfig);
        return $globalAdapter;
    }

    protected function getLocalCache()
    {
        $localCache = null;
        $localCacheConfig = $this->xml->localCache;
        if ($localCacheConfig) {
            $localCache = $this->getCache($localCacheConfig);
        }
        return $localCache;
    }

    public function getManager()
    {
        if (!$this->manager instanceof ManagerInterface) {
            $managerClass = Manager::class;
            if (isset($this->xml->manager['class'])) {
                $managerClass = $this->xml->manager['class'];
            }
            $reflectionClass = new \ReflectionClass($managerClass);
            if ($managerClass == Manager::class) {
                // just a shortcut so I don't have to rewrite some complicated unit tests.  I'm just lazy.
                $this->manager = new Manager($this->getLocalCache(), $this->getBuilder(), $this->getRemoteCache());
                return $this->manager;
            }
            if (!$reflectionClass->implementsInterface(ManagerInterface::class)) {
                throw new InvalidConfigurationException('Manager class must implement ' . ManagerInterface::class);
            }
            $manager = $reflectionClass->newInstance();
            /* @var $manager ManagerInterface */
            $manager->setBuilder($this->getBuilder());
            $manager->setLocalCache($this->getLocalCache());
            $manager->setRemoteCache($this->getRemoteCache());
            $this->manager = $manager;
        }
        return $this->manager;
    }

}

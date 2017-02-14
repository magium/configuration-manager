<?php

namespace Magium\Configuration;

use Magium\Configuration\Config\Builder;
use Magium\Configuration\Config\BuilderFactory;
use Magium\Configuration\Config\BuilderFactoryInterface;
use Magium\Configuration\Config\InvalidConfigurationLocationException;
use Magium\Configuration\Config\MissingConfigurationException;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\Manager\CacheFactory;
use Magium\Configuration\Manager\Manager;

class MagiumConfigurationFactory
{
    protected $file;
    protected $xml;

    protected $manager;
    protected $builder;
    protected $baseDir;
    protected $contextFile;

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
    }

    public function getContextFile()
    {
        if (!$this->contextFile) {
//            if ($this->)
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

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        if (!$this->builder instanceof Builder) {
            $builderFactoryConfig = $this->xml->builderFactory;
            $class = (string)$builderFactoryConfig['class'];
            if (!$class) {
                $class = BuilderFactory::class; // das default
            }
            $reflection = new \ReflectionClass($class);
            if (!$reflection->implementsInterface(BuilderFactoryInterface::class)) {
                throw new InvalidConfigurationException($class . ' must implement ' . BuilderFactoryInterface::class);
            }
            $builderFactory = $reflection->newInstance($this->xml);
            if (!$builderFactory instanceof BuilderFactoryInterface) {
                throw new InvalidConfigurationException(
                    sprintf(
                        'The builder factory %s must implement %s',
                        get_class($builderFactory),
                        BuilderFactoryInterface::class
                    )
                );
            }
            $this->builder = $builderFactory->getBuilder();
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
        if (!$this->manager instanceof Manager) {
            $this->manager = new Manager($this->getRemoteCache(), $this->getBuilder(), $this->getLocalCache());
        }
        return $this->manager;
    }

}

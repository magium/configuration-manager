<?php

namespace Magium\Configuration;

use Magium\Configuration\Config\BuilderFactory;
use Magium\Configuration\Config\BuilderFactoryInterface;
use Magium\Configuration\Config\BuilderInterface;
use Magium\Configuration\Config\Context;
use Magium\Configuration\Config\MissingConfigurationException;
use Magium\Configuration\Config\Repository\ConfigurationRepository;
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
    protected $context = ConfigurationRepository::CONTEXT_DEFAULT;

    protected static $me;

    public function __construct($magiumConfigurationFile = null, $context = ConfigurationRepository::CONTEXT_DEFAULT, $cwd = __DIR__)
    {
        self::$me = $this;
        if ($context instanceof Context) {
            $context = $context->getContext();
        }
        $this->context = $context;
        if (!$magiumConfigurationFile) {
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
        $this->overrideWithEnvironmentVariables();
    }

    protected function overrideWithEnvironmentVariables()
    {
        $document = dom_import_simplexml($this->xml)->ownerDocument;
        $xpath = new \DOMXPath($document);
        $elements = $xpath->query('//*');
        foreach ($elements as $element) {
            if ($element instanceof \DOMElement) {
                $paths = [];
                do {
                    $paths[] = $element->nodeName;
                } while ($element = $element->parentNode);

                // Get rid of the base node and base document.  They mean nothing to us.
                array_pop($paths);
                array_pop($paths);
                if (!empty($paths)) {
                    $paths = array_reverse($paths);
                    $path = implode('/', $paths);
                    $value = $this->getEnvironmentVariableOverride($path);
                    if ($value !== null) {
                        foreach ($paths as &$path) {
                            $path = 's:' . $path;
                        }
                        $path = implode('/', $paths);
                        $xpathExpression = '/s:magiumBase/' . $path;
                        $this->xml->registerXPathNamespace('s', 'http://www.magiumlib.com/BaseConfiguration');
                        $simpleXmlElement = $this->xml->xpath($xpathExpression);
                        if (!empty($simpleXmlElement)) {
                            $simpleXmlElement = $simpleXmlElement[0];
                            $simpleXmlElement[0] = $value; // self reference
                        }
                    }
                }

            }
        }
    }

    /**
     * @return \SimpleXMLElement
     */

    public function getXml()
    {
        return $this->xml;
    }

    public function getMagiumConfigurationFilePath()
    {
        return $this->file;
    }

    protected static function getInstance($magiumConfigurationFile = null, $context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        if (!self::$me instanceof self) {
            new self($magiumConfigurationFile, $context);
        }
        return self::$me;
    }

    public static function configurationFactory($magiumConfigurationFile = null, $context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        $me = self::getInstance($magiumConfigurationFile, $context);
        return $me->getConfiguration($context);
    }

    public static function builderFactory($magiumConfigurationFile = null, $context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        $me = self::getInstance($magiumConfigurationFile, $context);
        return $me->getBuilder();
    }

    public static function managerFactory($magiumConfigurationFile = null, $context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        $me = self::getInstance($magiumConfigurationFile, $context);
        return $me->getManager();
    }

    public function getConfiguration($context = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        return $this->getManager()->getConfiguration($context);
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getEnvironmentVariableOverride($path)
    {
        $pathTranslated = 'MCM_' . str_replace('/', '_', strtoupper($path));
        $value = getenv($pathTranslated);
        if (!$value) {
            return null;
        }
        return $value;
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
            $this->builderFactory = $reflection->newInstance(new \SplFileInfo($this->baseDir), $this->xml, $this->getContextFile());
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
                $managerClass = (string)$this->xml->manager['class'];
            }
            $reflectionClass = new \ReflectionClass($managerClass);
            if ($managerClass == Manager::class) {
                // just a shortcut so I don't have to rewrite some complicated unit tests.  I'm just lazy.
                $this->manager = new Manager($this->getRemoteCache(), $this->getBuilder(), $this->getLocalCache());
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

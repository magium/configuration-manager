<?php

namespace Magium\Configuration\Config;

use Magium\Configuration\Config\Storage\RelationalDatabase;
use Magium\Configuration\File\Configuration\ConfigurationFileRepository;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\InvalidConfigurationException;
use Magium\Configuration\Manager\CacheFactory;
use Zend\Db\Adapter\Adapter;

class BuilderFactory implements BuilderFactoryInterface
{
    protected $configuration;
    protected $adapter;
    protected $contextFile;
    protected $baseDirectory;

    public function __construct(
        \SplFileInfo $baseDirectory,
        \SimpleXMLElement $configuration,
        AbstractContextConfigurationFile $contextConfigurationFile
    )
    {
        $this->configuration = $configuration;
        $this->contextFile = $contextConfigurationFile;
        if (!$baseDirectory->isDir()) {
            throw new InvalidConfigurationException('Base directory must be a directory');
        }
        $this->baseDirectory = $baseDirectory;
    }

    protected function getCache(\SimpleXMLElement $element)
    {
        $cacheFactory = new CacheFactory();
        return $cacheFactory->getCache($element);
    }

    public function getAdapter()
    {
        if (!$this->adapter instanceof Adapter) {
            $config = json_encode($this->configuration->persistenceConfiguration);
            $config = json_decode($config, true);
            $this->adapter = new Adapter($config);
        }
        return $this->adapter;
    }

    public function getPersistence()
    {
        $persistence = new RelationalDatabase($this->getAdapter(), $this->contextFile );
        return $persistence;
    }

    public function getSecureBaseDirectories()
    {
        $cwd = getcwd();
        chdir($this->baseDirectory->getPath());
        $config = $this->configuration->configurationDirectories;
        $config = json_encode($config);
        $config = json_decode($config, true);
        $baseDirs = [];
        if (is_array($config) && isset($config['directory'])) {
            if (!is_array($config['directory'])) {
                $config['directory'] = [$config['directory']];
            }
            foreach ($config['directory'] as $dir) {
                $path = realpath($dir);
                if (!is_dir($path)) {
                    throw new InvalidConfigurationLocationException('A secure configuration path cannot be determined for the directory: ' . $dir);
                }
                $baseDirs[] = $path;
            }
        }
        chdir($cwd);
        return $baseDirs;
    }

    public function getConfigurationFiles(array $secureBaseDirectories = [])
    {
        $config = json_encode($this->configuration->configurationFiles);
        $config = json_decode($config, true);
        $files = [];
        if (isset($config['file'])) {
            if (!is_array($config['file'])) {
                $config['file'] = [$config['file']];
            }
            foreach ($config['file'] as $file) {
                $found = false;
                foreach ($secureBaseDirectories as $base) {
                    chdir($base);
                    $path = realpath($file);
                    if ($path) {
                        $found = true;
                        $files[] = $path;
                    }
                }
                if (!$found) {
                    throw new InvalidConfigurationLocationException('Could not find file: ' . $file);
                }
            }
        }
        return $files;
    }

    public function getBuilder()
    {
        // This method expects that chdir() has been called on the same level as the magium-configuration.xml file
        $cache = $this->getCache($this->configuration->cache);
        $persistence = $this->getPersistence();
        $secureBases = $this->getSecureBaseDirectories();
        $configurationFiles = $this->getConfigurationFiles($secureBases);
        $repository = new ConfigurationFileRepository($secureBases, $configurationFiles);

        /*
         * We only populate up to the secureBases because adding a DIC or service manager by configuration starts
         * making the configuration-based approach pay off less.  If you need a DIC or service manager for your
         * configuration builder (which you will if you use object/method callbacks for value filters) then you need
         * to wire the Builder object with your own code.
         */

        $builder = new Builder(
            $cache,
            $persistence,
            $repository
        );

        return $builder;
    }

}

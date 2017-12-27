<?php

namespace Magium\Configuration\Config;

use Magium\Configuration\Config\Storage\Mongo;
use Magium\Configuration\Config\Storage\RelationalDatabase;
use Magium\Configuration\File\Configuration\ConfigurationFileRepository;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\InvalidConfigurationException;
use Magium\Configuration\Manager\CacheFactory;
use MongoDB\Client;
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

    public function getDatabaseConfiguration()
    {
        $config = json_encode($this->configuration->persistenceConfiguration);
        $config = json_decode($config, true);
        return $config;
    }

    public function getRelationalAdapter()
    {
        if (!class_exists(Adapter::class)) {
            throw new \Exception('Please make sure you have zendframework/zend-db installed for data storage');
        }
        if (!$this->adapter instanceof Adapter) {
            $config = $this->getDatabaseConfiguration();
            $this->adapter = new Adapter($config);
        }
        return $this->adapter;
    }

    public function getMongoDsnString()
    {
        $config = $this->getDatabaseConfiguration();
        $dsn = 'mongodb://';  //[username:password@]host1[:port1][,host2[:port2],...[,hostN[:portN]]][/[database][?options]]';
        $at = false;
        if (!empty($config['username'])) {
            $dsn .= $config['username'];
            $at = true;
        }
        if (!empty($config['password'])) {
            $dsn .= ':'.$config['password'];
            $at = true;
        }
        if ($at) {
            $dsn .= '@';
        }
        $dsn .= $config['hostname'];
        if (!empty($config['port'])) {
            $dsn .= ':' . $config['port'];
        }
        return $dsn;
    }

    public function getMongoAdapter()
    {
        if (!class_exists(Client::class)) {
            throw new \Exception('Please make sure you have mongodb/mongodb installed for data storage');
        }
        $config = $this->getDatabaseConfiguration();
        $dsn = $this->getMongoDsnString();
        $client = new Client($dsn);
        $collection = empty($config['table'])?Mongo::TABLE:$config['table'];
        return new Mongo($client->selectCollection($config['database'], $collection));
    }

    public function getPersistence()
    {
        if (empty($this->configuration->persistenceConfiguration->driver)) {
            throw new \Exception('Please set your driver type either corresponding to its Zend DB adapter '
                . 'name or the specific document database, such as mongo.  Ensure that you have either installed '
                . 'zendframework/zend-db or mongodb/mongodb depending on where you want to store your configuration.');
        }
        if (empty($this->configuration->persistenceConfiguration->database)) {
            throw new \Exception('You must specify a database in your persistenceConfiguration');
        }
        if (stripos($this->configuration->persistenceConfiguration->driver, 'mongo') === 0) {
            return $this->getMongoAdapter();
        }
        $persistence = new RelationalDatabase($this->getRelationalAdapter(), $this->contextFile);
        return $persistence;
    }

    public function getSecureBaseDirectories()
    {
        $cwd = getcwd();
        $path = $this->baseDirectory->getRealPath();
        chdir($path);
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
        if (!empty($config['file'])) {
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
        $repository = ConfigurationFileRepository::getInstance($secureBases, $configurationFiles);

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

<?php

namespace Magium\Configuration\File\Configuration;

use Magium\Configuration\Config\InvalidConfigurationLocationException;
use Magium\Configuration\Config\InvalidDirectoryException;
use Magium\Configuration\File\AdapterInterface;
use Magium\Configuration\File\InvalidFileException;

class ConfigurationFileRepository implements \ArrayAccess, \Iterator, \Countable
{
    protected $files = [];
    protected $secureBases = [];

    public function __construct(array $secureBases = [], array $configurationFiles = [])
    {
        foreach ($secureBases as $base) {
            $this->addSecureBase($base);
        }

        $supportedTypes = $this->getSupportedTypes($configurationFiles);
        $this->buildConfigurationFileList($configurationFiles, $supportedTypes);

    }

    protected function buildConfigurationFileList(array $configurationFiles, array $supportedTypes)
    {
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

    protected function getSupportedTypes(array $configurationFiles)
    {
        $supportedTypes = [];
        if (!empty($configurationFiles)) {
            $checkSupportedTypes = glob(__DIR__ . '/*File.php');
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
        return $supportedTypes;
    }

    public function count()
    {
        return count($this->files);
    }

    public function registerConfigurationFile(AdapterInterface $file)
    {
        if (isset($this->files[$file->getFile()])) {
            throw new InvalidFileException('The file has been added already: ' . $file->getFile());
        }
        $this->files[$file->getFile()] = $file;
    }

    public function addSecureBase($base)
    {
        $path = realpath($base);
        if (!is_dir($path)) {
            throw new InvalidDirectoryException('Unable to determine real path for directory: ' . $base);
        }
        $this->secureBases[] = $path;
    }

    protected function checkFileLocation(AdapterInterface $file)
    {
        $path = realpath($file->getFile());
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

    public function current()
    {
        $current = current($this->files);
        $this->checkFileLocation($current);
        return $current;
    }

    public function next()
    {
        $next = next($this->files);
        if (!is_bool($next)) {
            $this->checkFileLocation($next);
        }
        return $next;
    }

    public function key()
    {
        return key($this->files);
    }

    public function valid()
    {
        $key = $this->key();
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

    public function rewind()
    {
        return reset($this->files);
    }

    public function offsetExists($offset)
    {
        return isset($this->files[$offset]);
    }

    public function offsetGet($offset)
    {
        $get = $this->files[$offset];
        $this->checkFileLocation($get);
        return $get;
    }

    public function offsetSet($offset, $value)
    {
        $this->registerConfigurationFile($value);
    }

    public function offsetUnset($offset)
    {
        unset($this->files[$offset]);
    }

}

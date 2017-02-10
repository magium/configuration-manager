<?php

namespace Magium\Configuration;

class MagiumConfigurationFactory
{
    protected $file;
    protected $xml;

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
        $this->xml = simplexml_load_file($magiumConfigurationFile);
    }

    public function validateConfigurationFile()
    {
        $doc = new \DOMDocument();
        $doc->load($this->file);
        $result = $doc->schemaValidate(__DIR__ . '/../assets/magium-configuration.xsd');
        return $result;
    }

    public function getManager()
    {

    }

}

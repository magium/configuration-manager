<?php

namespace Magium\Configuration\File;

use Magium\Configuration\Config\MergedStructure;

trait XmlFileToXmlTrait
{

    protected $xml;

    abstract public function getFile();

    abstract public function validateSchema(\DOMDocument $doc);

    public function toXml()
    {
        if (!$this->xml instanceof MergedStructure) {
            $file = $this->getFile();
            $content = file_get_contents($file);
            $doc = new \DOMDocument();
            $doc->loadXML($content);
            $this->validateSchema($doc);
            $this->xml = new MergedStructure($content);
        }
        return $this->xml;
    }

}

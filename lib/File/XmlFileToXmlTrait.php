<?php

namespace Magium\Configuration\File;

trait XmlFileToXmlTrait
{

    protected $xml;

    abstract public function getFile();

    abstract public function validateSchema(\DOMDocument $doc);

    public function toXml()
    {
        if (!$this->xml instanceof \SimpleXMLElement) {
            $file = $this->getFile();
            $content = file_get_contents($file);
            $doc = new \DOMDocument();
            $doc->loadXML($content);
            $this->validateSchema($doc);
            $this->xml = new \SimpleXMLElement($content);
        }
        return $this->xml;
    }

}

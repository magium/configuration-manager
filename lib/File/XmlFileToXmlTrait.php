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
            $doc = new \DOMDocument();
            $doc->load($file);
            $this->validateSchema($doc);
            $this->xml = simplexml_import_dom($doc);
        }
        return $this->xml;
    }

}

<?php

namespace Magium\Configuration\File;

trait XmlFileToXmlTrait
{

    abstract function getFile();

    abstract function validateSchema(\DOMDocument $doc);

    public function toXml()
    {
        $file = $this->getFile();
        $doc = new \DOMDocument();
        $doc->load($file);
        $this->validateSchema($doc);
        return simplexml_import_dom($doc);
    }

}
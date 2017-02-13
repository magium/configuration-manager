<?php

namespace Magium\Configuration\File;

trait XmlFileToXmlTrait
{

    abstract public function getFile();

    abstract public function validateSchema(\DOMDocument $doc);

    public function toXml()
    {
        $file = $this->getFile();
        $doc = new \DOMDocument();
        $doc->load($file);
        $this->validateSchema($doc);
        return simplexml_import_dom($doc);
    }

}

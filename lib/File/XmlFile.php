<?php

namespace Magium\Configuration\File;

class XmlFile extends AbstractAdapter
{

    public function toXml()
    {
        $file = $this->getFile();
        $doc = new \DOMDocument();
        $doc->load($file);
        $this->validateSchema($doc);
        return simplexml_import_dom($doc);
    }

}
<?php

namespace Magium\Configuration\File;

abstract class AbstractAdapter implements AdapterInterface
{

    protected $file;

    public function __construct(
        $file
    )
    {
        $this->file = realpath($file);
        if ($this->file === false) {
            throw new InvalidFileException('Unable to find file: ' . $file);
        }
    }

    /*
     * @return string The location of the file
     */

    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param \DOMDocument $doc
     * @throws InvalidFileStructureException
     */

    protected function validateSchema(\DOMDocument $doc)
    {
        try {
            $schema = realpath(__DIR__ . '/../../assets/configuration-element.xsd');
            $element = $doc->firstChild;
            if ($element instanceof \DOMElement) {
                $element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.magiumlib.com/Configuration');
                $element->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://www.magiumlib.com/Configuration ' . $schema);
            }
            $out = $doc->saveXML();
            $validateDoc = new \DOMDocument();
            $validateDoc->loadXML($out);
            $validateDoc->schemaValidate($schema);
        } catch (\Exception $e) {
            throw new InvalidFileStructureException(sprintf('Unable to load file %s due to exception: %s', $this->getFile(), $e->getMessage()), $e->getCode(), $e);
        }
    }
}
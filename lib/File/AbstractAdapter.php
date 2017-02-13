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

    abstract protected function configureSchema(\DOMElement $element);

    /**
     * @param \DOMDocument $doc
     * @throws InvalidFileStructureException
     */

    protected function validateSchema(\DOMDocument $doc)
    {
        try {
            $element = $doc->firstChild;
            if (!$element instanceof \DOMElement) {
                // This is more for code completion.  If the first child is not a DOMElement, PHP is probably broken
                throw new \Exception('Invalid XML file');
            }
            $schema = $this->configureSchema($element);
            $out = $doc->saveXML();
            $validateDoc = new \DOMDocument();
            $validateDoc->loadXML($out);
            $validateDoc->schemaValidate($schema);
        } catch (\Exception $e) {
            throw new InvalidFileStructureException(sprintf('Unable to load file %s due to exception: %s', $this->getFile(), $e->getMessage()), $e->getCode(), $e);
        }
    }
}

<?php

namespace Magium\Configuration\File;

interface AdapterInterface
{

    /**
     * Returns a realpath() filtered filename
     *
     * @return string
     */

    public function getFile();

    /**
     * @return \SimpleXMLElement
     */

    public function toXml();

}

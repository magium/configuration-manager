<?php

namespace Magium\Configuration\View\Controllers\Helpers;

use Magium\Configuration\Config\Repository\ConfigInterface;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;

class ContextRepository
{

    protected $contextFile;

    public function __construct(
        AbstractContextConfigurationFile $contextFile
    )
    {
        $this->contextFile = $contextFile;
    }

    public function getContextHierarchy()
    {
        $contextsFile = $this->getContextFile();
        $xml = $contextsFile->toXml();
        $children = [];
        foreach ($xml->children() as $child) {
            $children[] = $this->buildContextArray($child);
        }
        $contexts = [[
            'id' => 'default',
            'label' => 'Default',
            'children' => $children
        ]];

        return $contexts;
    }

    public function getContextList()
    {
        $contexts = [
            ConfigInterface::CONTEXT_DEFAULT   => 'Default'
        ];
        $xml = $this->contextFile->toXml();
        $xml->registerXPathNamespace('s', 'http://www.magiumlib.com/ConfigurationContext');
        $nodes = $this->contextFile->toXml()->xpath('//s:context');
        foreach ($nodes as $node) {
            $id = $label = (string)$node['id'];
            if (isset($node['label'])) {
                $label = (string)$node['label'];
            }
            $contexts[$id] = $label;
        }

        return $contexts;
    }

    public function getContextFile()
    {
        return $this->contextFile;
    }

    private function buildContextArray(\SimpleXMLElement $element)
    {
        $children = [];
        foreach ($element->children() as $child) {
            $children[] = $this->buildContextArray($child);
        }
        $label = (string)$element['id'];
        if (isset($element['label'])) {
            $label = (string)$element['label'];
        }
        $contexts = [
            'id' => (string)$element['id'],
            'label' => $label,
            'children' => $children
        ];
        return $contexts;
    }

}

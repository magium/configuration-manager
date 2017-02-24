<?php

namespace Magium\Configuration\File\Configuration;

class PhpFile extends AbstractConfigurationFile
{

    public function toXml()
    {
        $file = $this->getFile();
        $results = include $file;
        $config = new \SimpleXMLElement('<configuration />');
        foreach ($results as $section => $sectionData) {
            $sectionObj = $config->addChild('section');
            $sectionObj['identifier'] = $section;
            if (isset($sectionData['name'])) {
                $sectionObj['name'] = $sectionData['name'];
            }
            if (isset($sectionData['groups'])) {
                $this->processGroups($sectionObj, $sectionData['groups']);
            }
        }

        $doc = new \DOMDocument();
        $node = dom_import_simplexml($config);
        $newNode = $doc->importNode($node, true);
        $doc->appendChild($newNode);
        $this->validateSchema($doc);

        return $config;
    }

    protected function processGroups(\SimpleXMLElement $section, $groups)
    {
        foreach ($groups as $groupId => $groupData) {
            $group = $section->addChild('group');
            $group['identifier'] = $groupId;
            if (isset($groupData['name'])) {
                $group['name'] = $groupData['name'];
            }
            if (isset($groupData['elements'])) {
                $this->processElements($group, $groupData['elements']);
            }
        }
    }

    protected function processElements(\SimpleXMLElement $group, $elements)
    {
        foreach ($elements as $id => $data) {
            $element = $group->addChild('element');
            $element['identifier'] = $id;
            if (isset($data['name'])) {
                $element['name'] = $data['name'];
            }
            if (isset($data['source'])) {
                $element['source'] = $data['source'];
            }
            if (isset($data['type'])) {
                $element['type'] = $data['type'];
            }
        }
    }
}

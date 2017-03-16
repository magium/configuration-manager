<?php

namespace Magium\Configuration\File\Configuration;

class PhpFile extends AbstractConfigurationFile
{

    public function toXml()
    {
        $file = $this->getFile();
        $results = include $file;
        $config = new \SimpleXMLElement('<magiumConfiguration />');
        foreach ($results as $section => $sectionData) {
            $sectionObj = $config->addChild('section');
            $sectionObj['identifier'] = $section;
            if (isset($sectionData['label'])) {
                $sectionObj['label'] = $sectionData['label'];
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
            if (isset($groupData['label'])) {
                $group['label'] = $groupData['label'];
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
            if (isset($data['label'])) {
                $element['label'] = $data['label'];
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

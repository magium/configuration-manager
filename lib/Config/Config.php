<?php

namespace Magium\Configuration\Config;

class Config extends \SimpleXMLElement
{

    const CONTEXT_DEFAULT = 'default';

    protected static $allowedTrues = [
        true, 'true', 1, '1', 'on', 'yes'
    ];

    public function getValue($path)
    {
        list($section, $group, $element) = explode('/', $path);
        $xpath = sprintf('/*/%s/%s/%s', $section, $group, $element);
        $element = $this->xpath($xpath);
        if (empty($element)) {
            return null;
        }
        $value = (string)$element[0];
        return $value;
    }

    public function getValueFlag($path)
    {
        $value = $this->getValue($path);
        foreach (self::$allowedTrues as $true) {
            if ($value === $true) {
                return true;
            }
        }
        return false;
    }
}
